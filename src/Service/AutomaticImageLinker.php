<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Mail;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Family\Listing as FamilyListing;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Frame\Listing as FrameListing;
use Pimcore\Model\DataObject\Model as ModelObject;
use Pimcore\Model\DataObject\Model\Listing as ModelListing;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Notification\Service\NotificationService;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\User;
use ZipArchive;

final class AutomaticImageLinker
{
    public const SETTING_FOLDER = 'automatic_image_linking_folder';
    public const SETTING_EMAILS = 'automatic_image_linking_report_emails';
    public const SETTING_NOTIFICATION_USERS = 'automatic_image_linking_notification_users';

    /** @var array<string, string> */
    private const FIELD_TOKEN_MAP = [
        'facebook' => 'facebookImageGallery',
        'fb' => 'facebookImageGallery',
        'instagram' => 'instagramImageGallery',
        'ig' => 'instagramImageGallery',
        'quality' => 'qualityControlImages',
        'qc' => 'qualityControlImages',
        'outline' => 'outlineImage',
        'tooling' => 'toolingSamplesGallery',
        'sample' => 'toolingSamplesGallery',
        'samples' => 'toolingSamplesGallery',
    ];

    private bool $extractingZip = false;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    public function processFolder(Asset\Folder $folder, ?User $user = null, bool $notify = true): array
    {
        $result = $this->emptyResult();
        $listing = new Asset\Listing();
        $listing->setCondition('path LIKE ?', [$folder->getRealFullPath() . '/%']);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        foreach ($listing->load() as $asset) {
            if ($asset instanceof Asset\Image) {
                $result = $this->mergeResult($result, $this->processImage($asset));
            } elseif ($this->isZipAsset($asset)) {
                $result = $this->mergeResult($result, $this->processZip($asset, $user, false));
            }
        }

        if ($notify) {
            $this->sendReport($result, $folder, $user);
        }

        return $result;
    }

    /**
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    public function processAsset(Asset $asset, ?User $user = null, bool $notify = true): array
    {
        if ($asset instanceof Asset\Image) {
            $result = $this->processImage($asset);
        } elseif ($this->isZipAsset($asset)) {
            $result = $this->processZip($asset, $user, false);
        } else {
            $result = $this->emptyResult();
        }

        if ($notify) {
            $this->sendReport($result, $asset, $user);
        }

        return $result;
    }

    public function shouldAutoProcess(Asset $asset): bool
    {
        if ($this->extractingZip || (!$asset instanceof Asset\Image && !$this->isZipAsset($asset))) {
            return false;
        }

        $folder = $this->getConfiguredFolder();

        return $folder instanceof Asset\Folder
            && str_starts_with($asset->getRealFullPath(), rtrim($folder->getRealFullPath(), '/') . '/');
    }

    public function getConfiguredFolder(): ?Asset\Folder
    {
        $value = $this->getSettingString(self::SETTING_FOLDER);
        if ($value === '') {
            return null;
        }

        $asset = is_numeric($value) ? Asset::getById((int) $value) : Asset::getByPath($value);

        return $asset instanceof Asset\Folder ? $asset : null;
    }

    /**
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    private function processImage(Asset\Image $asset): array
    {
        $result = $this->emptyResult();
        $result['processed'][] = $this->assetSummary($asset);

        $match = $this->matchAsset($asset);
        if ($match === null) {
            $result['orphan'][] = [
                ...$this->assetSummary($asset),
                'reason' => 'No family/model/frame code matched the filename',
            ];

            return $result;
        }

        try {
            $changed = $this->linkImage($match['object'], $asset, $match['field']);
            $match['object']->save();
            $result['linked'][] = [
                ...$this->assetSummary($asset),
                'objectId' => (int) $match['object']->getId(),
                'objectPath' => $match['object']->getRealFullPath(),
                'className' => $match['object']->getClassName(),
                'code' => $match['code'],
                'field' => $match['field'],
                'changed' => $changed,
            ];
        } catch (\Throwable $e) {
            $result['errors'][] = sprintf('%s: %s', $asset->getRealFullPath(), $e->getMessage());
        }

        return $result;
    }

    /**
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    private function processZip(Asset $asset, ?User $user, bool $notify): array
    {
        $result = $this->emptyResult();
        if ($asset->getCustomSetting('automaticImageLinkingExtracted') === true) {
            return $result;
        }

        $zipPath = $asset->getTemporaryFile();
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            $result['errors'][] = sprintf('Unable to open ZIP asset %s', $asset->getRealFullPath());

            return $result;
        }

        $parent = Asset::getById((int) $asset->getParentId());
        if (!$parent instanceof Asset\Folder) {
            $result['errors'][] = sprintf('ZIP asset %s has no folder parent', $asset->getRealFullPath());
            $zip->close();

            return $result;
        }

        $this->extractingZip = true;
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === '' || str_ends_with($name, '/') || !$this->isSupportedImageFilename($name)) {
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    $result['errors'][] = sprintf('Unable to read %s from %s', $name, $asset->getRealFullPath());
                    continue;
                }

                $filename = $this->uniqueAssetFilename($parent, basename($name));
                $image = Asset::create((int) $parent->getId(), [
                    'filename' => $filename,
                    'data' => $content,
                    'userOwner' => $user?->getId(),
                    'userModification' => $user?->getId(),
                ], false);
                $image->save(['automatic_image_linking_skip_event' => true]);

                if (!$image instanceof Asset\Image) {
                    $result['errors'][] = sprintf('%s from %s was not detected as an image', $name, $asset->getRealFullPath());
                    continue;
                }

                $result['extracted'][] = $image->getRealFullPath();
                $result = $this->mergeResult($result, $this->processImage($image));
            }
        } finally {
            $this->extractingZip = false;
            $zip->close();
        }

        $asset->setCustomSetting('automaticImageLinkingExtracted', true);
        $asset->save(['automatic_image_linking_skip_event' => true]);

        if ($notify) {
            $this->sendReport($result, $asset, $user);
        }

        return $result;
    }

    /**
     * @return array{object: Concrete, code: string, field: string}|null
     */
    private function matchAsset(Asset\Image $asset): ?array
    {
        $parsed = $this->parseFilename($asset->getFilename());
        foreach ($parsed['codeCandidates'] as $codeCandidate) {
            foreach ([Frame::class, ModelObject::class, Family::class] as $className) {
                $object = $this->findObjectByCode($className, $codeCandidate);
                if (!$object instanceof Concrete) {
                    continue;
                }

                $field = $this->resolveAllowedField($object, $parsed['field']);
                if ($field === null) {
                    continue;
                }

                return [
                    'object' => $object,
                    'code' => (string) $object->get('code'),
                    'field' => $field,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{field: string, codeCandidates: list<string>}
     */
    private function parseFilename(string $filename): array
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $tokens = array_values(array_filter(preg_split('/[-_\s]+/', $basename) ?: []));
        $field = 'imageGallery';
        $codeTokens = [];

        foreach ($tokens as $token) {
            $normalized = strtolower($token);
            if (isset(self::FIELD_TOKEN_MAP[$normalized])) {
                $field = self::FIELD_TOKEN_MAP[$normalized];
                continue;
            }

            $codeTokens[] = $token;
        }

        $candidates = [];
        for ($length = count($codeTokens); $length >= 1; $length--) {
            $candidateTokens = array_slice($codeTokens, 0, $length);
            foreach (['-', ' ', '_'] as $separator) {
                $candidates[] = strtoupper(implode($separator, $candidateTokens));
            }
            if ($length > 1) {
                $candidates[] = strtoupper(implode('-', array_slice($candidateTokens, 0, -1)) . ' ' . end($candidateTokens));
            }
        }

        return [
            'field' => $field,
            'codeCandidates' => array_values(array_unique($candidates)),
        ];
    }

    /**
     * @param class-string $className
     */
    private function findObjectByCode(string $className, string $code): ?Concrete
    {
        $listing = match ($className) {
            Frame::class => new FrameListing(),
            ModelObject::class => new ModelListing(),
            Family::class => new FamilyListing(),
            default => null,
        };

        if ($listing === null) {
            return null;
        }

        $listing->setUnpublished(true);
        $listing->setCondition('code = ?', [$code]);
        $listing->setLimit(1);
        $objects = $listing->load();

        return $objects[0] ?? null;
    }

    private function resolveAllowedField(Concrete $object, string $requestedField): ?string
    {
        $class = $object->getClass();
        if ($class->getFieldDefinition($requestedField) !== null) {
            return $requestedField;
        }

        return $class->getFieldDefinition('imageGallery') !== null ? 'imageGallery' : null;
    }

    private function linkImage(Concrete $object, Asset\Image $asset, string $field): bool
    {
        return match ($field) {
            'outlineImage' => $this->setSingleImage($object, $asset, $field),
            'qualityControlImages' => $this->appendElementMetadata($object, $asset, $field),
            default => $this->appendImageGallery($object, $asset, $field),
        };
    }

    private function setSingleImage(Concrete $object, Asset\Image $asset, string $field): bool
    {
        if (($object->get($field) instanceof Asset) && (int) $object->get($field)->getId() === (int) $asset->getId()) {
            return false;
        }

        $object->set($field, $asset);

        return true;
    }

    private function appendImageGallery(Concrete $object, Asset\Image $asset, string $field): bool
    {
        $gallery = $object->get($field);
        $items = $gallery instanceof ImageGallery ? $gallery->getItems() : [];

        foreach ($items as $item) {
            if ($item instanceof Hotspotimage && (int) $item->getImage()?->getId() === (int) $asset->getId()) {
                return false;
            }
        }

        $items[] = new Hotspotimage($asset);
        $object->set($field, new ImageGallery($items));

        return true;
    }

    private function appendElementMetadata(Concrete $object, Asset\Image $asset, string $field): bool
    {
        $items = $object->get($field);
        $items = is_array($items) ? $items : [];

        foreach ($items as $item) {
            if ($item instanceof ElementMetadata && $item->getElement()?->getId() === $asset->getId()) {
                return false;
            }
        }

        $items[] = new ElementMetadata($field, [], $asset);
        $object->set($field, $items);

        return true;
    }

    /**
     * @param array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>} $result
     */
    private function sendReport(array $result, Asset $context, ?User $user): void
    {
        if ($result['processed'] === [] && $result['extracted'] === [] && $result['errors'] === []) {
            return;
        }

        $subject = sprintf(
            'Automatic image linking: %d linked, %d orphan',
            count($result['linked']),
            count($result['orphan'])
        );
        $html = $this->renderHtmlReport($result, $context);
        $text = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

        foreach ($this->getConfiguredEmails() as $email) {
            try {
                (new Mail())
                    ->to($email)
                    ->subject($subject)
                    ->html($html)
                    ->text($text)
                    ->send();
            } catch (\Throwable $e) {
                // Keep the linking job successful even when reporting is misconfigured.
            }
        }

        foreach ($this->getConfiguredNotificationUsers() as $recipient) {
            try {
                $this->notificationService->sendToUser(
                    (int) $recipient->getId(),
                    (int) ($user?->getId() ?? 0),
                    $subject,
                    $text,
                    $context
                );
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * @param array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>} $result
     */
    private function renderHtmlReport(array $result, Asset $context): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<h2>Automatic image linking report</h2>';
        $html .= '<p>Context: ' . $escape($context->getRealFullPath()) . '</p>';
        $html .= '<p>Processed: ' . count($result['processed']) . ' | Linked: ' . count($result['linked']) . ' | Orphan: ' . count($result['orphan']) . ' | Errors: ' . count($result['errors']) . '</p>';

        $html .= '<h3>Linked images</h3><ul>';
        foreach ($result['linked'] as $row) {
            $html .= '<li>' . $escape($row['path']) . ' -> ' . $escape($row['objectPath']) . ' / ' . $escape($row['field']) . ($row['changed'] ? '' : ' (already linked)') . '</li>';
        }
        $html .= $result['linked'] === [] ? '<li>None</li>' : '';
        $html .= '</ul><h3>Orphan images</h3><ul>';
        foreach ($result['orphan'] as $row) {
            $html .= '<li>' . $escape($row['path']) . ' - ' . $escape($row['reason']) . '</li>';
        }
        $html .= $result['orphan'] === [] ? '<li>None</li>' : '';
        $html .= '</ul>';

        if ($result['errors'] !== []) {
            $html .= '<h3>Errors</h3><ul>';
            foreach ($result['errors'] as $error) {
                $html .= '<li>' . $escape($error) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * @return list<string>
     */
    private function getConfiguredEmails(): array
    {
        return $this->splitSetting(self::SETTING_EMAILS);
    }

    /**
     * @return list<User>
     */
    private function getConfiguredNotificationUsers(): array
    {
        $users = [];
        foreach ($this->splitSetting(self::SETTING_NOTIFICATION_USERS) as $identifier) {
            $user = is_numeric($identifier) ? User::getById((int) $identifier) : User::getByName($identifier);
            if ($user instanceof User) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * @return list<string>
     */
    private function splitSetting(string $name): array
    {
        $value = $this->getSettingString($name);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $value) ?: [])));
    }

    private function getSettingString(string $name): string
    {
        try {
            $setting = Predefined::getByKey($name);
        } catch (\Throwable) {
            return '';
        }

        if (!$setting instanceof Predefined) {
            return '';
        }

        return trim((string) $setting->getData());
    }

    private function isZipAsset(Asset $asset): bool
    {
        return strtolower(pathinfo($asset->getFilename(), PATHINFO_EXTENSION)) === 'zip';
    }

    private function isSupportedImageFilename(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tif', 'tiff'], true);
    }

    private function uniqueAssetFilename(Asset\Folder $folder, string $filename): string
    {
        $safeFilename = ElementService::getValidKey($filename, 'asset');
        $path = rtrim($folder->getRealFullPath(), '/') . '/';
        if (Asset::getByPath($path . $safeFilename) === null) {
            return $safeFilename;
        }

        $extension = pathinfo($safeFilename, PATHINFO_EXTENSION);
        $base = pathinfo($safeFilename, PATHINFO_FILENAME);
        for ($i = 2; $i < 1000; $i++) {
            $candidate = $base . '-' . $i . ($extension !== '' ? '.' . $extension : '');
            if (Asset::getByPath($path . $candidate) === null) {
                return $candidate;
            }
        }

        return uniqid($base . '-', true) . ($extension !== '' ? '.' . $extension : '');
    }

    /**
     * @return array{id: int, path: string, filename: string}
     */
    private function assetSummary(Asset $asset): array
    {
        return [
            'id' => (int) $asset->getId(),
            'path' => $asset->getRealFullPath(),
            'filename' => $asset->getFilename(),
        ];
    }

    /**
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    private function emptyResult(): array
    {
        return [
            'processed' => [],
            'linked' => [],
            'orphan' => [],
            'errors' => [],
            'extracted' => [],
        ];
    }

    /**
     * @param array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>} $left
     * @param array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>} $right
     *
     * @return array{processed: list<array<string, mixed>>, linked: list<array<string, mixed>>, orphan: list<array<string, mixed>>, errors: list<string>, extracted: list<string>}
     */
    private function mergeResult(array $left, array $right): array
    {
        foreach (array_keys($left) as $key) {
            $left[$key] = array_merge($left[$key], $right[$key]);
        }

        return $left;
    }
}
