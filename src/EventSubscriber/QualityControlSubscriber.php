<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class QualityControlSubscriber implements EventSubscriberInterface
{
    public const PERMISSION_KEY = 'quality_control';

    private const BASE_FOLDER_PROPERTY_KEY = 'quality_control_asset_folder';
    private const TARGET_FOLDER_PROPERTY_KEY = 'quality_control_target_folder';
    private const SUPPORTED_CLASS_NAMES = ['family', 'model', 'frame'];
    private const LAYOUT_PANEL_NAME = 'qualityControl';
    private const LEGACY_TARGET_FOLDER_FIELD = 'qualityControlTargetFolder';
    private const DOCUMENTS_FIELD = 'qualityControlDocuments';
    private const IMAGES_FIELD = 'qualityControlImages';
    private const REMARKS_FIELD = 'qualityControlRemarks';
    private const RELATION_FIELDS = [
        self::DOCUMENTS_FIELD,
        self::IMAGES_FIELD,
    ];
    private const HIDDEN_FIELDS = [
        self::LEGACY_TARGET_FOLDER_FIELD,
        self::DOCUMENTS_FIELD,
        self::IMAGES_FIELD,
        self::REMARKS_FIELD,
    ];
    private const EDITABLE_FIELDS = [
        self::DOCUMENTS_FIELD,
        self::IMAGES_FIELD,
        self::REMARKS_FIELD,
    ];
    private const REMARK_COLUMNS = [
        'createdAt',
        'createdBy',
        'type',
        'status',
        'remark',
    ];

    public function __construct(
        private TokenStorageUserResolver $userResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -10],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -10],
            'pimcore.dataobject.get.preSendData' => 'onPreSendData',
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

        $this->restoreUnauthorizedFields($object);
        $this->stampRemarksRows($object);
        $this->syncTargetFolderProperty($object);
        $this->moveQualityControlAssets($object);
    }

    public function onPreSendData(GenericEvent $event): void
    {
        $object = $event->getArgument('object');
        if (!$object instanceof Concrete || !$this->supportsObject($object)) {
            return;
        }

        $data = $event->getArgument('data');
        if (!is_array($data)) {
            return;
        }

        if (!$this->userCanAccessQualityControl()) {
            if (isset($data['layout'])) {
                $this->removeQualityControlLayout($data['layout']);
            }

            if (isset($data['data']) && is_array($data['data'])) {
                foreach (self::HIDDEN_FIELDS as $fieldName) {
                    unset($data['data'][$fieldName]);
                }
            }

            if (isset($data['metaData']) && is_array($data['metaData'])) {
                foreach (self::HIDDEN_FIELDS as $fieldName) {
                    unset($data['metaData'][$fieldName]);
                }
            }

            $event->setArgument('data', $data);
        }
    }

    private function supportsObject(Concrete $object): bool
    {
        return in_array(strtolower((string) $object->getClassName()), self::SUPPORTED_CLASS_NAMES, true);
    }

    private function userCanAccessQualityControl(): bool
    {
        $user = $this->userResolver->getUser();

        if (!$user instanceof User) {
            return true;
        }

        return $user->getAdmin() || $user->isAllowed(self::PERMISSION_KEY);
    }

    private function restoreUnauthorizedFields(Concrete $object): void
    {
        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->getAdmin() || $user->isAllowed(self::PERMISSION_KEY)) {
            return;
        }

        $persisted = $object->getId() > 0 ? Concrete::getById($object->getId(), ['force' => true]) : null;
        foreach (self::EDITABLE_FIELDS as $fieldName) {
            $this->writeFieldValue(
                $object,
                $fieldName,
                $persisted instanceof Concrete ? $this->readFieldValue($persisted, $fieldName) : null
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function syncTargetFolderProperty(Concrete $object): void
    {
        $targetFolderPath = $this->resolveTargetFolderPath($object, false);
        if ($targetFolderPath === null) {
            $object->removeProperty(self::TARGET_FOLDER_PROPERTY_KEY);

            return;
        }

        $propertyDefinition = Predefined::getByKey(self::TARGET_FOLDER_PROPERTY_KEY);
        $propertyType = $propertyDefinition instanceof Predefined ? trim((string) $propertyDefinition->getType()) : '';

        $object->setProperty(
            self::TARGET_FOLDER_PROPERTY_KEY,
            $propertyType !== '' ? $propertyType : 'text',
            $targetFolderPath,
            false,
            $propertyDefinition instanceof Predefined && $propertyDefinition->getInheritable()
        );

        $this->getOrCreateFolder($targetFolderPath);
    }

    private function stampRemarksRows(Concrete $object): void
    {
        $rows = $this->readFieldValue($object, self::REMARKS_FIELD);
        if (!is_array($rows)) {
            return;
        }

        $normalizedRows = [];
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i');
        $userLabel = $this->resolveCurrentUserLabel();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalizedRow = [];
            foreach (self::REMARK_COLUMNS as $columnName) {
                $value = $row[$columnName] ?? '';
                $normalizedRow[$columnName] = is_scalar($value) ? trim((string) $value) : '';
            }

            if ($this->isEmptyRow($normalizedRow)) {
                continue;
            }

            if ($normalizedRow['createdAt'] === '') {
                $normalizedRow['createdAt'] = $timestamp;
            }

            if ($normalizedRow['createdBy'] === '' && $userLabel !== '') {
                $normalizedRow['createdBy'] = $userLabel;
            }

            $normalizedRows[] = $normalizedRow;
        }

        $this->writeFieldValue($object, self::REMARKS_FIELD, $normalizedRows === [] ? null : $normalizedRows);
    }

    private function resolveCurrentUserLabel(): string
    {
        $user = $this->userResolver->getUser();
        if (!$user instanceof User) {
            return '';
        }

        $name = trim((string) ($user->getName() ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($user->getUsername() ?? ''));
    }

    /**
     * @param array<string, string> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    private function moveQualityControlAssets(Concrete $object): void
    {
        $targetFolder = null;

        foreach (self::RELATION_FIELDS as $fieldName) {
            $relations = $this->readFieldValue($object, $fieldName);
            if (!is_array($relations)) {
                continue;
            }

            foreach ($relations as $relation) {
                $asset = $this->extractAsset($relation);
                if (!$asset instanceof Asset || $asset instanceof AssetFolder) {
                    continue;
                }

                if (!$targetFolder instanceof AssetFolder) {
                    $targetFolderPath = $this->resolveTargetFolderPath($object, true);
                    if ($targetFolderPath === null) {
                        continue;
                    }

                    $targetFolder = $this->getOrCreateFolder($targetFolderPath);
                }

                $this->moveAssetToFolder($asset, $targetFolder);
            }
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

    /**
     * @throws ValidationException
     */
    private function resolveTargetFolderPath(Concrete $object, bool $required): ?string
    {
        $baseFolderPath = $this->getBaseFolderPath($required);
        if ($baseFolderPath === null) {
            return null;
        }

        $segments = $this->buildHierarchySegments($object, $required);
        if ($segments === []) {
            return null;
        }

        return ElementService::correctPath(
            ($baseFolderPath === '/' ? '' : $baseFolderPath) . '/' . implode('/', $segments)
        );
    }

    /**
     * @return list<string>
     *
     * @throws ValidationException
     */
    private function buildHierarchySegments(Concrete $object, bool $required): array
    {
        $segments = [];
        $current = $object;

        for ($depth = 0; $depth < 5 && $current instanceof Concrete; $depth++) {
            if ($this->supportsObject($current)) {
                $segment = $this->buildObjectFolderSegment($current, $required);
                if ($segment === null) {
                    return [];
                }

                $segments[] = $segment;
            }

            $parent = $current->getParent();
            if (!$parent instanceof Concrete) {
                break;
            }

            $current = $parent;
        }

        return array_reverse($segments);
    }

    /**
     * @throws ValidationException
     */
    private function buildObjectFolderSegment(Concrete $object, bool $required): ?string
    {
        $identifier = '';

        if (method_exists($object, 'getCode')) {
            $identifier = trim((string) ($object->getCode() ?? ''));
        }

        if ($identifier === '') {
            $identifier = trim((string) $object->getKey());
        }

        if ($identifier === '') {
            if ($required) {
                throw new ValidationException(sprintf(
                    'Set the Code before saving quality control assets for %s.',
                    strtolower((string) $object->getClassName())
                ));
            }

            return null;
        }

        $segment = ElementService::getValidKey($identifier, 'asset');
        if ($segment === '') {
            if ($required) {
                throw new ValidationException(sprintf(
                    'The Code "%s" cannot be converted into a valid quality control asset folder name.',
                    $identifier
                ));
            }

            return null;
        }

        return $segment;
    }

    /**
     * @throws ValidationException
     */
    private function getBaseFolderPath(bool $required): ?string
    {
        $property = Predefined::getByKey(self::BASE_FOLDER_PROPERTY_KEY);
        if (!$property instanceof Predefined) {
            if ($required) {
                throw new ValidationException(sprintf(
                    'Missing predefined property "%s" for quality control assets.',
                    self::BASE_FOLDER_PROPERTY_KEY
                ));
            }

            return null;
        }

        $folderPath = trim((string) $property->getData());
        if ($folderPath === '') {
            if ($required) {
                throw new ValidationException(sprintf(
                    'Predefined property "%s" must contain an asset folder path.',
                    self::BASE_FOLDER_PROPERTY_KEY
                ));
            }

            return null;
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
                'Quality control target path "%s" exists but is not an asset folder.',
                $folderPath
            ));
        }

        if ($existing instanceof AssetFolder) {
            return $existing;
        }

        $folder = AssetService::createFolderByPath($folderPath);
        if (!$folder instanceof AssetFolder) {
            throw new ValidationException(sprintf('Could not create quality control asset folder "%s".', $folderPath));
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
            throw new ValidationException('The quality control asset cannot be moved because it has no filename.');
        }

        $asset->setFilename(ElementService::getSafeCopyName($filename, $targetFolder));
        $asset->setParent($targetFolder);

        try {
            $asset->save();
        } catch (\Throwable $e) {
            throw new ValidationException(sprintf(
                'Could not move quality control asset "%s" to "%s": %s',
                $filename,
                $targetFolder->getRealFullPath(),
                $e->getMessage()
            ), 0, $e);
        }
    }

    private function removeQualityControlLayout(mixed $layout): void
    {
        if (!is_object($layout) || !method_exists($layout, 'getChildren') || !method_exists($layout, 'setChildren')) {
            return;
        }

        $children = $layout->getChildren();
        if (!is_array($children)) {
            return;
        }

        $filteredChildren = [];
        foreach ($children as $child) {
            $name = is_object($child) && method_exists($child, 'getName') ? $child->getName() : null;
            if ($name === self::LAYOUT_PANEL_NAME) {
                continue;
            }

            $this->removeQualityControlLayout($child);
            $filteredChildren[] = $child;
        }

        $layout->setChildren(array_values($filteredChildren));
    }

    private function readFieldValue(Concrete $object, string $fieldName): mixed
    {
        $getter = 'get' . ucfirst($fieldName);
        if (!method_exists($object, $getter)) {
            return null;
        }

        if (in_array($fieldName, self::RELATION_FIELDS, true)) {
            try {
                return $object->$getter(['unpublished' => true]);
            } catch (\Throwable) {
                // Fall through to the default getter call below.
            }
        }

        return $object->$getter();
    }

    private function writeFieldValue(Concrete $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);
        if (!method_exists($object, $setter)) {
            return;
        }

        $object->$setter($value);
    }

    private function isVersionOnlyOrAutoSave(DataObjectEvent $event): bool
    {
        return ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true);
    }
}
