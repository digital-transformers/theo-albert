<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Property\Predefined;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MarketingMaterialsAssetSubscriber implements EventSubscriberInterface
{
    private const BASE_FOLDER_PROPERTY_KEY = 'marketing_materials_asset_folder';
    private const SUPPORTED_CLASS_NAMES = ['family', 'model', 'frame'];
    private const GALLERY_FIELDS = [
        'imageGallery',
        'facebookImageGallery',
        'instagramImageGallery',
    ];
    private const VIDEO_FIELD = 'video';
    private const ATTACHMENTS_FIELD = 'attachments';

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -10],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -10],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function onPreSave(DataObjectEvent $event): void
    {
        if ($this->isVersionOnlyOrAutoSave($event)) {
            return;
        }

        $object = $event->getObject();
        if (!$object instanceof Concrete || !$this->supportsObject($object)) {
            return;
        }

        $targetFolder = null;
        foreach ($this->collectMarketingAssets($object) as $asset) {
            if ($asset instanceof AssetFolder) {
                continue;
            }

            if (!$targetFolder instanceof AssetFolder) {
                $targetFolder = $this->getOrCreateFolder($this->resolveTargetFolderPath($object));
            }

            $this->moveAssetToFolder($asset, $targetFolder);
        }
    }

    private function isVersionOnlyOrAutoSave(DataObjectEvent $event): bool
    {
        return ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true);
    }

    private function supportsObject(Concrete $object): bool
    {
        return in_array(strtolower((string) $object->getClassName()), self::SUPPORTED_CLASS_NAMES, true);
    }

    /**
     * @return list<Asset>
     */
    private function collectMarketingAssets(Concrete $object): array
    {
        $previousInheritedValues = \Pimcore\Model\DataObject::getGetInheritedValues();
        \Pimcore\Model\DataObject::setGetInheritedValues(false);

        try {
            $assets = [];

            foreach (self::GALLERY_FIELDS as $fieldName) {
                $gallery = $this->readFieldValue($object, $fieldName);
                if (!$gallery instanceof ImageGallery) {
                    continue;
                }

                foreach ($gallery->getItems() as $item) {
                    if (!$item instanceof Hotspotimage) {
                        continue;
                    }

                    $image = $item->getImage();
                    if ($image instanceof Asset) {
                        $assets[] = $image;
                    }
                }
            }

            $video = $this->readFieldValue($object, self::VIDEO_FIELD);
            if ($video instanceof Video) {
                $data = $video->getData();
                if ($data instanceof Asset) {
                    $assets[] = $data;
                }

                $poster = $video->getPoster();
                if ($poster instanceof Asset) {
                    $assets[] = $poster;
                }
            }

            $attachments = $this->readFieldValue($object, self::ATTACHMENTS_FIELD);
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $asset = $this->extractAsset($attachment);
                    if ($asset instanceof Asset) {
                        $assets[] = $asset;
                    }
                }
            }

            return $assets;
        } finally {
            \Pimcore\Model\DataObject::setGetInheritedValues($previousInheritedValues);
        }
    }

    private function extractAsset(mixed $relation): ?Asset
    {
        if ($relation instanceof Asset) {
            return $relation;
        }

        if ($relation instanceof ElementMetadata) {
            $element = $relation->getElement();

            return $element instanceof Asset ? $element : null;
        }

        return null;
    }

    private function readFieldValue(Concrete $object, string $fieldName): mixed
    {
        $getter = 'get' . ucfirst($fieldName);
        if (!method_exists($object, $getter)) {
            return null;
        }

        return $object->$getter();
    }

    /**
     * @throws ValidationException
     */
    private function resolveTargetFolderPath(Concrete $object): string
    {
        $baseFolderPath = $this->getBaseFolderPath();
        $segments = $this->buildHierarchySegments($object);

        return ElementService::correctPath(
            ($baseFolderPath === '/' ? '' : $baseFolderPath) . '/' . implode('/', $segments)
        );
    }

    /**
     * @return list<string>
     *
     * @throws ValidationException
     */
    private function buildHierarchySegments(Concrete $object): array
    {
        $hierarchy = [];
        $current = $object;

        for ($depth = 0; $depth < 10 && $current instanceof AbstractObject; $depth++) {
            if ($current instanceof Concrete && $this->supportsObject($current)) {
                $hierarchy[] = [
                    'className' => strtolower((string) $current->getClassName()),
                    'segment' => $this->buildObjectFolderSegment($current),
                ];
            }

            $current = $current->getParent();
        }

        $hierarchy = array_reverse($hierarchy);
        $classNames = array_column($hierarchy, 'className');
        $objectClassName = strtolower((string) $object->getClassName());

        if ($objectClassName === 'model' && !in_array('family', $classNames, true)) {
            throw new ValidationException('Place the model below a family before saving Marketing materials assets.');
        }

        if (
            $objectClassName === 'frame'
            && (!in_array('family', $classNames, true) || !in_array('model', $classNames, true))
        ) {
            throw new ValidationException('Place the frame below a model and family before saving Marketing materials assets.');
        }

        return array_values(array_map(
            static fn (array $item): string => $item['segment'],
            $hierarchy
        ));
    }

    /**
     * @throws ValidationException
     */
    private function buildObjectFolderSegment(Concrete $object): string
    {
        if (!method_exists($object, 'getCode')) {
            throw new ValidationException(sprintf(
                'The %s Code field is not available for Marketing materials folder creation.',
                strtolower((string) $object->getClassName())
            ));
        }

        $code = trim((string) ($object->getCode() ?? ''));
        if ($code === '') {
            throw new ValidationException(sprintf(
                'Set the %s Code before saving Marketing materials assets.',
                strtolower((string) $object->getClassName())
            ));
        }

        $identifier = $code;
        if (strtolower((string) $object->getClassName()) === 'family' && method_exists($object, 'getName')) {
            $name = trim((string) ($object->getName() ?? ''));
            if ($name !== '') {
                $identifier .= ' | ' . $name;
            }
        }

        $segment = ElementService::getValidKey($identifier, 'asset');
        if ($segment !== '') {
            return $segment;
        }

        throw new ValidationException(sprintf(
            'The Code "%s" cannot be converted into a valid Marketing materials asset folder name.',
            $code
        ));
    }

    /**
     * @throws ValidationException
     */
    private function getBaseFolderPath(): string
    {
        $property = Predefined::getByKey(self::BASE_FOLDER_PROPERTY_KEY);
        if (!$property instanceof Predefined) {
            throw new ValidationException(sprintf(
                'Missing predefined property "%s" for Marketing materials assets.',
                self::BASE_FOLDER_PROPERTY_KEY
            ));
        }

        $folderPath = trim((string) $property->getData());
        if ($folderPath === '') {
            throw new ValidationException(sprintf(
                'Predefined property "%s" must contain an asset folder path.',
                self::BASE_FOLDER_PROPERTY_KEY
            ));
        }

        return $this->normalizeAssetFolderPath($folderPath);
    }

    private function normalizeAssetFolderPath(string $folderPath): string
    {
        if (!str_starts_with($folderPath, '/')) {
            $folderPath = '/' . $folderPath;
        }

        return ElementService::correctPath($folderPath);
    }

    /**
     * @throws ValidationException
     */
    private function getOrCreateFolder(string $folderPath): AssetFolder
    {
        $existing = Asset::getByPath($folderPath);
        if ($existing instanceof Asset && !$existing instanceof AssetFolder) {
            throw new ValidationException(sprintf(
                'Marketing materials target path "%s" exists but is not an asset folder.',
                $folderPath
            ));
        }

        if ($existing instanceof AssetFolder) {
            return $existing;
        }

        $folder = AssetService::createFolderByPath($folderPath);
        if (!$folder instanceof AssetFolder) {
            throw new ValidationException(sprintf('Could not create Marketing materials asset folder "%s".', $folderPath));
        }

        return $folder;
    }

    /**
     * @throws ValidationException
     */
    private function moveAssetToFolder(Asset $asset, AssetFolder $targetFolder): void
    {
        if ($asset->getParentId() === $targetFolder->getId()) {
            return;
        }

        $filename = (string) $asset->getFilename();
        if ($filename === '') {
            throw new ValidationException('The Marketing materials asset cannot be moved because it has no filename.');
        }

        $asset->setFilename(ElementService::getSafeCopyName($filename, $targetFolder));
        $asset->setParent($targetFolder);

        try {
            $asset->save();
        } catch (\Throwable $e) {
            throw new ValidationException(sprintf(
                'Could not move Marketing materials asset "%s" to "%s": %s',
                $filename,
                $targetFolder->getRealFullPath(),
                $e->getMessage()
            ), 0, $e);
        }
    }
}
