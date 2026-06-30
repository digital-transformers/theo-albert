<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Listing as ObjectListing;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ProductPermissionSubscriber implements EventSubscriberInterface
{
    public const PERMISSION_FAMILY_PHASE_UPDATE = 'family_phase_update';
    public const PERMISSION_FAMILY_LAUNCH_UPDATE = 'family_launch_update';
    public const PERMISSION_SUPPLIER_PROJECTS_ONLY = 'supplier_projects_only';
    public const PERMISSION_MODEL_FRAME_GENERATE = 'model_frame_generate';
    public const PERMISSION_QUALITY_CONTROL_ONLY = 'quality_control_only';
    public const PERMISSION_MARKETING_ONLY = 'marketing_only';
    public const PERMISSION_AUTOMATIC_IMAGE_LINKING = 'automatic_image_linking';

    private const FAMILY_CLASS_NAME = 'family';
    private const SUPPLIER_CLASS_NAME = 'supplier';
    private const PRODUCT_CLASS_NAMES = ['family', 'model', 'frame'];
    private const PHASE_FIELDS = ['phase'];
    private const LAUNCH_FIELDS = ['launchPeriod', 'launchYear'];
    private const QUALITY_CONTROL_FIELDS = [
        'qualityControlTargetFolder',
        'qualityControlDocuments',
        'qualityControlImages',
        'qualityControlRemarks',
    ];
    private const MARKETING_FIELDS = [
        'imageGallery',
        'facebookImageGallery',
        'instagramImageGallery',
        'video',
        'attachments',
        'publicationChannels',
        'workingTitle',
        'internalFollowupDesigner',
        'magicMechanismScore',
        'localizedfields',
        'storytellingShortText',
        'storytellingLongText',
    ];

    private bool $collectingSupplierObjects = false;

    /**
     * @var array<int, list<int>>
     */
    private array $supplierObjectIdsByUserId = [];

    public function __construct(
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -20],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -20],
            'pimcore.admin.object.list.beforeListLoad' => 'onBeforeListLoad',
            'pimcore.dataobject.get.preSendData' => 'onPreSendData',
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        if ($this->isVersionOnlyOrAutoSave($event)) {
            return;
        }

        $object = $event->getObject();
        if (!$object instanceof Concrete) {
            return;
        }

        $this->denyUnauthorizedSupplierSave($object);

        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->isAdmin()) {
            return;
        }

        if ($this->isSupportedProductObject($object) && $user->isAllowed(self::PERMISSION_QUALITY_CONTROL_ONLY)) {
            $this->restoreFieldsExcept($object, self::QUALITY_CONTROL_FIELDS);

            return;
        }

        if ($this->isSupportedProductObject($object) && $user->isAllowed(self::PERMISSION_MARKETING_ONLY)) {
            $this->restoreFieldsExcept($object, self::MARKETING_FIELDS);

            return;
        }

        if (strtolower((string) $object->getClassName()) !== self::FAMILY_CLASS_NAME) {
            return;
        }

        $persisted = $object->getId() > 0 ? Concrete::getById((int) $object->getId(), ['force' => true]) : null;
        if (!$user->isAllowed(self::PERMISSION_FAMILY_PHASE_UPDATE)) {
            $this->restoreFields($object, $persisted, self::PHASE_FIELDS);
        }

        if (!$user->isAllowed(self::PERMISSION_FAMILY_LAUNCH_UPDATE)) {
            $this->restoreFields($object, $persisted, self::LAUNCH_FIELDS);
        }
    }

    public function onBeforeListLoad(GenericEvent $event): void
    {
        $user = $this->userResolver->getUser();
        if (!$user instanceof User || !$this->shouldLimitToSupplierProjects($user)) {
            return;
        }

        $list = $event->getArgument('list');
        if (!is_object($list) || !method_exists($list, 'addConditionParam') || $this->collectingSupplierObjects) {
            return;
        }

        $ids = $this->getSupplierProjectObjectIds($user);
        if ($ids === []) {
            $list->addConditionParam('id = ?', [-1]);

            return;
        }

        $list->addConditionParam('id IN (?)', [$ids]);
    }

    public function onPreSendData(GenericEvent $event): void
    {
        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->isAdmin()) {
            return;
        }

        $object = $event->getArgument('object');
        if (!$object instanceof Concrete) {
            return;
        }

        if ($this->shouldLimitToSupplierProjects($user) && !$this->isObjectAllowedForSupplierUser($object, $user)) {
            $data = $event->getArgument('data');
            if (is_array($data)) {
                $data['permissions']['view'] = false;
                $data['permissions']['edit'] = false;
                $data['permissions']['publish'] = false;
                $data['permissions']['delete'] = false;
                $event->setArgument('data', $data);
            }

            return;
        }

        if ($this->shouldLimitToSupplierProjects($user)) {
            $data = $event->getArgument('data');
            if (is_array($data)) {
                $className = strtolower((string) $object->getClassName());
                if ($className !== 'model') {
                    $data['permissions']['edit'] = false;
                    $data['permissions']['publish'] = false;
                    $data['permissions']['delete'] = false;
                    $event->setArgument('data', $data);
                }
            }
        }

        if (!$this->isSupportedProductObject($object)) {
            return;
        }

        $data = $event->getArgument('data');
        if (!is_array($data) || !isset($data['layout'])) {
            return;
        }

        if ($user->isAllowed(self::PERMISSION_QUALITY_CONTROL_ONLY)) {
            $this->makeFieldsEditableOnly($data['layout'], self::QUALITY_CONTROL_FIELDS);
        } elseif ($user->isAllowed(self::PERMISSION_MARKETING_ONLY)) {
            $this->makeFieldsEditableOnly($data['layout'], self::MARKETING_FIELDS);
        } elseif (strtolower((string) $object->getClassName()) === self::FAMILY_CLASS_NAME) {
            if (!$user->isAllowed(self::PERMISSION_FAMILY_PHASE_UPDATE)) {
                $this->makeFieldsNotEditable($data['layout'], self::PHASE_FIELDS);
            }

            if (!$user->isAllowed(self::PERMISSION_FAMILY_LAUNCH_UPDATE)) {
                $this->makeFieldsNotEditable($data['layout'], self::LAUNCH_FIELDS);
            }
        }

        $event->setArgument('data', $data);
    }

    /**
     * @param list<string> $editableFieldNames
     */
    private function restoreFieldsExcept(Concrete $object, array $editableFieldNames): void
    {
        $persisted = $object->getId() > 0 ? Concrete::getById((int) $object->getId(), ['force' => true]) : null;
        if (!$persisted instanceof Concrete) {
            return;
        }

        foreach (array_keys($object->getClass()->getFieldDefinitions()) as $fieldName) {
            if (in_array($fieldName, $editableFieldNames, true)) {
                continue;
            }

            $this->writeFieldValue($object, $fieldName, $this->readFieldValue($persisted, $fieldName));
        }
    }

    /**
     * @param list<string> $fieldNames
     */
    private function restoreFields(Concrete $object, ?Concrete $persisted, array $fieldNames): void
    {
        foreach ($fieldNames as $fieldName) {
            $this->writeFieldValue(
                $object,
                $fieldName,
                $persisted instanceof Concrete ? $this->readFieldValue($persisted, $fieldName) : null
            );
        }
    }

    /**
     * @param list<string> $fieldNames
     */
    private function makeFieldsNotEditable(mixed $layout, array $fieldNames): void
    {
        if (!is_object($layout)) {
            return;
        }

        $name = method_exists($layout, 'getName') ? (string) $layout->getName() : '';
        if (in_array($name, $fieldNames, true) && method_exists($layout, 'setNoteditable')) {
            $layout->setNoteditable(true);
        }

        if (!method_exists($layout, 'getChildren')) {
            return;
        }

        $children = $layout->getChildren();
        if (!is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            $this->makeFieldsNotEditable($child, $fieldNames);
        }
    }

    /**
     * @param list<string> $editableFieldNames
     */
    private function makeFieldsEditableOnly(mixed $layout, array $editableFieldNames): void
    {
        if (!is_object($layout)) {
            return;
        }

        $name = method_exists($layout, 'getName') ? (string) $layout->getName() : '';
        if ($name !== '' && !in_array($name, $editableFieldNames, true) && method_exists($layout, 'setNoteditable')) {
            $layout->setNoteditable(true);
        }

        if (!method_exists($layout, 'getChildren')) {
            return;
        }

        $children = $layout->getChildren();
        if (!is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            $this->makeFieldsEditableOnly($child, $editableFieldNames);
        }
    }

    private function isSupportedProductObject(Concrete $object): bool
    {
        return in_array(strtolower((string) $object->getClassName()), self::PRODUCT_CLASS_NAMES, true);
    }

    private function shouldLimitToSupplierProjects(User $user): bool
    {
        return !$user->isAdmin() && $user->isAllowed(self::PERMISSION_SUPPLIER_PROJECTS_ONLY);
    }

    private function denyUnauthorizedSupplierSave(mixed $object): void
    {
        if (!$object instanceof Concrete) {
            return;
        }

        $user = $this->userResolver->getUser();
        if (!$user instanceof User || !$this->shouldLimitToSupplierProjects($user)) {
            return;
        }

        $className = strtolower((string) $object->getClassName());
        if (!in_array($className, self::PRODUCT_CLASS_NAMES, true)) {
            return;
        }

        if ($className !== 'model' || !$this->isObjectAllowedForSupplierUser($object, $user)) {
            throw new AccessDeniedHttpException('Supplier users may only update involved model objects.');
        }
    }

    /**
     * @return list<int>
     */
    private function getSupplierProjectObjectIds(User $user): array
    {
        $userId = (int) $user->getId();
        if (isset($this->supplierObjectIdsByUserId[$userId])) {
            return $this->supplierObjectIdsByUserId[$userId];
        }

        $this->collectingSupplierObjects = true;
        try {
            $supplier = $this->resolveSupplierObject($user);
            if (!$supplier instanceof Concrete) {
                return $this->supplierObjectIdsByUserId[$userId] = [];
            }

            $ids = [];
            $listing = new ObjectListing();
            $listing->setUnpublished(true);
            $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT]);
            $listing->setCondition('className IN (?)', [self::PRODUCT_CLASS_NAMES]);

            foreach ($listing->load() as $object) {
                if ($object instanceof Concrete && $this->isObjectInvolvedWithSupplier($object, $supplier)) {
                    $ids[] = (int) $object->getId();
                }
            }

            return $this->supplierObjectIdsByUserId[$userId] = array_values(array_unique($ids));
        } finally {
            $this->collectingSupplierObjects = false;
        }
    }

    private function isObjectAllowedForSupplierUser(Concrete $object, User $user): bool
    {
        return in_array((int) $object->getId(), $this->getSupplierProjectObjectIds($user), true);
    }

    private function resolveSupplierObject(User $user): ?Concrete
    {
        $tokens = array_filter(array_unique([
            strtolower(trim((string) $user->getName())),
            strtolower(trim((string) $user->getUsername())),
        ]));

        if ($tokens === []) {
            return null;
        }

        $listing = new ObjectListing();
        $listing->setUnpublished(true);
        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT]);
        $listing->setCondition('className = ?', [self::SUPPLIER_CLASS_NAME]);

        foreach ($listing->load() as $object) {
            if (!$object instanceof Concrete) {
                continue;
            }

            $supplierTokens = array_filter(array_unique([
                strtolower(trim((string) $object->getKey())),
                strtolower(trim((string) $this->readFieldValue($object, 'code'))),
                strtolower(trim((string) $this->readFieldValue($object, 'name'))),
            ]));

            if (array_intersect($tokens, $supplierTokens) !== []) {
                return $object;
            }
        }

        return null;
    }

    private function isObjectInvolvedWithSupplier(Concrete $object, Concrete $supplier): bool
    {
        $className = strtolower((string) $object->getClassName());

        if ($className === self::SUPPLIER_CLASS_NAME) {
            return (int) $object->getId() === (int) $supplier->getId();
        }

        foreach (['suppliers', 'supplier', 'templeTipSupplier'] as $fieldName) {
            if ($this->valueContainsObject($this->readFieldValue($object, $fieldName), $supplier)) {
                return true;
            }
        }

        if ($className === 'model') {
            foreach (['facepartReferenceSupplier', 'templeReferenceSupplier'] as $fieldName) {
                if ($this->stringMatchesSupplier((string) $this->readFieldValue($object, $fieldName), $supplier)) {
                    return true;
                }
            }

            $details = $this->readFieldValue($object, 'finalProductDetails');
            if (is_iterable($details)) {
                foreach ($details as $detail) {
                    if (is_object($detail) && method_exists($detail, 'getSupplier')) {
                        if ($this->valueContainsObject($detail->getSupplier(), $supplier)) {
                            return true;
                        }
                    }
                }
            }
        }

        $parent = $object->getParent();

        return $parent instanceof Concrete && $this->isObjectInvolvedWithSupplier($parent, $supplier);
    }

    private function valueContainsObject(mixed $value, Concrete $expectedObject): bool
    {
        if ($value instanceof ObjectMetadata) {
            $value = $value->getObject();
        }

        if ($value instanceof Concrete) {
            return (int) $value->getId() === (int) $expectedObject->getId();
        }

        if (is_iterable($value)) {
            foreach ($value as $item) {
                if ($this->valueContainsObject($item, $expectedObject)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function stringMatchesSupplier(string $value, Concrete $supplier): bool
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return false;
        }

        foreach (['code', 'name'] as $fieldName) {
            if ($value === strtolower(trim((string) $this->readFieldValue($supplier, $fieldName)))) {
                return true;
            }
        }

        return false;
    }

    private function readFieldValue(Concrete $object, string $fieldName): mixed
    {
        $getter = 'get' . ucfirst($fieldName);
        if (!method_exists($object, $getter)) {
            return null;
        }

        try {
            return $object->$getter(['unpublished' => true]);
        } catch (\Throwable) {
            return $object->$getter();
        }
    }

    private function writeFieldValue(Concrete $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);
        if (method_exists($object, $setter)) {
            $object->$setter($value);
        }
    }

    private function isVersionOnlyOrAutoSave(DataObjectEvent $event): bool
    {
        return ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true);
    }
}
