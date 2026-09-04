<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\AutomaticAssetMoveGuard;
use Closure;
use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data as DataDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Video;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Family\Listing as FamilyListing;
use Pimcore\Model\DataObject\Listing as ObjectListing;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LinkedFamilyPermissionSubscriber implements EventSubscriberInterface
{
    public const PERMISSION_DESIGNER = 'linked_family_designer';
    public const PERMISSION_SUPPLIER = 'linked_family_supplier';

    private bool $collectingObjects = false;

    /**
     * @var array<int, array{
     *     linked: bool,
     *     objectIds: list<int>,
     *     familyPaths: list<string>,
     *     navigationIds: list<int>,
     *     assetIds: list<int>,
     *     documentIds: list<int>
     * }>
     */
    private array $contextByUser = [];

    private readonly ?Closure $visibleIdsResolver;

    public function __construct(
        private readonly TokenStorageUserResolver $userResolver,
        private readonly AutomaticAssetMoveGuard $automaticAssetMoveGuard,
        ?callable $visibleIdsResolver = null,
    ) {
        $this->visibleIdsResolver = $visibleIdsResolver === null ? null : Closure::fromCallable($visibleIdsResolver);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreWrite', 30],
            DataObjectEvents::PRE_UPDATE => ['onPreWrite', 30],
            DataObjectEvents::PRE_DELETE => ['onPreWrite', 30],
            AssetEvents::PRE_ADD => ['onPreAddAsset', -30],
            AssetEvents::PRE_UPDATE => ['onPreWriteAsset', -30],
            AssetEvents::PRE_DELETE => ['onPreWriteAsset', -30],
            'pimcore.admin.object.list.beforeListLoad' => ['onBeforeListLoad', 20],
            AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD => ['onBeforeAssetListLoad', 20],
            AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD => ['onBeforeDocumentListLoad', 20],
            AdminEvents::OBJECT_GET_PRE_SEND_DATA => ['onPreSendData', 20],
            AdminEvents::ASSET_GET_PRE_SEND_DATA => ['onPreSendAssetData', 20],
            ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED => ['onElementPermissionIsAllowed', 20],
        ];
    }

    public function onBeforeListLoad(GenericEvent $event): void
    {
        $user = $this->linkedUser();
        if (!$user instanceof User || $this->collectingObjects) {
            return;
        }

        $list = $event->getArgument('list');
        if (!$list instanceof ObjectListing) {
            return;
        }

        if ($this->visibleIdsResolver !== null) {
            $ids = $this->resolvedTestIds($user);
            $list->addConditionParam('id IN (?)', [$ids === [] ? [-1] : $ids]);

            return;
        }

        $context = $this->linkedFamilyContext($user);
        if (!$context['linked']) {
            $list->addConditionParam('id = ?', [-1]);

            return;
        }

        if ($this->isSupplier($user)) {
            $ids = array_values(array_unique([...$context['objectIds'], ...$context['navigationIds']]));
            $list->addConditionParam('id IN (?)', [$ids === [] ? [-1] : $ids]);

            return;
        }

        $conditions = [];
        $parameters = [];
        if ($context['navigationIds'] !== []) {
            $conditions[] = 'id IN (?)';
            $parameters[] = $context['navigationIds'];
        }
        foreach ($context['familyPaths'] as $path) {
            $conditions[] = '(CONCAT(objects.path, objects.`key`) = ? OR CONCAT(objects.path, objects.`key`) LIKE ?)';
            $parameters[] = $path;
            $parameters[] = $path . '/%';
        }
        if ($this->isDesigner($user)) {
            $conditions[] = 'className = ?';
            $parameters[] = 'color';
        }

        $list->addConditionParam('(' . implode(' OR ', $conditions ?: ['id = ?']) . ')', $conditions === [] ? [-1] : $parameters);
    }

    public function onBeforeAssetListLoad(GenericEvent $event): void
    {
        $user = $this->linkedUser();
        if (!$user instanceof User || !$this->isSupplier($user) || $this->collectingObjects) {
            return;
        }

        $list = $event->getArgument('list');
        if (!is_object($list) || !method_exists($list, 'addConditionParam')) {
            return;
        }

        // Suppliers use assets only through related object fields; the asset tree stays empty.
        $ids = $this->visibleIdsResolver === null ? [] : $this->resolvedTestIds($user);
        $list->addConditionParam('id IN (?)', [$ids === [] ? [-1] : $ids]);
    }

    public function onBeforeDocumentListLoad(GenericEvent $event): void
    {
        $user = $this->linkedUser();
        if (!$user instanceof User || !$this->isSupplier($user) || $this->collectingObjects) {
            return;
        }

        $list = $event->getArgument('list');
        if (!is_object($list) || !method_exists($list, 'addConditionParam')) {
            return;
        }

        // Suppliers use documents only through related object fields; the document tree stays empty.
        $ids = $this->visibleIdsResolver === null ? [] : $this->resolvedTestIds($user);
        $list->addConditionParam('id IN (?)', [$ids === [] ? [-1] : $ids]);
    }

    public function onPreSendData(GenericEvent $event): void
    {
        $user = $this->linkedUser();
        if (!$user instanceof User) {
            return;
        }

        $object = $event->getArgument('object');
        $data = $event->getArgument('data');
        if (!$object instanceof Concrete || !is_array($data)) {
            return;
        }

        if (!$this->isObjectAllowed($object, $user)) {
            if ($this->isSupplier($user)) {
                throw new AccessDeniedHttpException('This object is not related to the current user SAPSupplier profile.');
            }

            $data['permissions']['view'] = false;
            $this->disableWritePermissions($data);
            $event->setArgument('data', $data);

            return;
        }

        if (!$this->isSupplier($user)) {
            return;
        }

        $this->setSupplierObjectPermissions($data);
        if (isset($data['layout'])) {
            $this->makeOnlyMediaFieldsEditable($data['layout']);
        }
        $event->setArgument('data', $data);
    }

    public function onPreWrite(DataObjectEvent $event): void
    {
        if (
            ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true)
        ) {
            return;
        }

        $user = $this->linkedUser();
        $object = $event->getObject();
        if (!$user instanceof User || !$object instanceof Concrete) {
            return;
        }

        if ($this->isSupplier($user)) {
            if (!$this->isObjectAllowed($object, $user)) {
                throw new AccessDeniedHttpException('Supplier users may only update objects related to their SAPSupplier profile.');
            }

            $persisted = $object->getId() > 0
                ? Concrete::getById((int) $object->getId(), ['force' => true])
                : null;
            if (!$persisted instanceof Concrete) {
                throw new AccessDeniedHttpException('Supplier users cannot create data objects.');
            }

            $this->restoreFieldsExcept($object, $persisted, $this->supplierMediaFields($object));
            $this->denyLinkingUnrelatedAssets($object, $persisted, $user);
            $object->setPublished($persisted->getPublished());

            return;
        }

        if (!$this->isObjectAllowed($object, $user)) {
            throw new AccessDeniedHttpException('Designer users may only update linked families, their children, and colors.');
        }
    }

    public function onPreAddAsset(AssetEvent $event): void
    {
        $user = $this->linkedUser();
        if (!$user instanceof User || !$this->isSupplier($user)) {
            return;
        }

        if (
            !($event->getAsset() instanceof AssetFolder)
            || $this->automaticAssetMoveGuard->isActive()
        ) {
            return;
        }

        throw new AccessDeniedHttpException('Supplier users cannot create asset folders.');
    }

    public function onPreWriteAsset(AssetEvent $event): void
    {
        $user = $this->linkedUser();
        if ($user instanceof User && $this->isSupplier($user)) {
            $asset = $event->getAsset();
            if (
                $this->automaticAssetMoveGuard->isActive()
                && !$asset instanceof AssetFolder
                && (int) $asset->getUserOwner() === (int) $user->getId()
            ) {
                return;
            }

            throw new AccessDeniedHttpException('Supplier users may upload new media but cannot modify existing assets.');
        }
    }

    public function onPreSendAssetData(GenericEvent $event): void
    {
        $user = $this->linkedUser();
        $asset = $event->getArgument('asset');
        $data = $event->getArgument('data');
        if (!$user instanceof User || !$this->isSupplier($user) || !$asset instanceof Asset || !is_array($data)) {
            return;
        }

        $allowed = $this->isSupplierElementAllowed($asset, $user);
        if (!$allowed) {
            throw new AccessDeniedHttpException('Supplier users may only view assets associated with their related objects.');
        }
        foreach (['publish', 'delete', 'rename', 'create', 'settings', 'properties'] as $permission) {
            $data['userPermissions'][$permission] = false;
        }
        $data['userPermissions']['list'] = $allowed;
        $data['userPermissions']['view'] = $allowed;
        $event->setArgument('data', $data);
    }

    public function onElementPermissionIsAllowed(ElementEvent $event): void
    {
        $user = $event->getArgument('user');
        if (!$user instanceof User || $user->isAdmin() || !$this->isSupplier($user)) {
            return;
        }

        $element = $event->getElement();
        $permission = (string) $event->getArgument('permissionType');
        $allowed = false;

        if ($element instanceof Concrete) {
            $context = $this->visibleIdsResolver === null ? $this->linkedFamilyContext($user) : null;
            $isRelated = in_array(
                (int) $element->getId(),
                $context === null ? $this->resolvedTestIds($user) : $context['objectIds'],
                true
            );
            $isNavigation = $context !== null
                && in_array((int) $element->getId(), $context['navigationIds'], true);
            $allowed = match ($permission) {
                'list', 'view' => $isRelated || $isNavigation,
                'save', 'publish' => $isRelated,
                default => false,
            };
        } elseif ($element instanceof AbstractObject) {
            $allowed = in_array($permission, ['list', 'view'], true)
                && in_array((int) $element->getId(), $this->linkedFamilyContext($user)['navigationIds'], true);
        } elseif ($element instanceof AssetFolder) {
            // Upload widgets need create permission on their target folder. The folder itself
            // remains hidden because list/view are deliberately denied.
            $allowed = $permission === 'create';
        } elseif ($element instanceof Asset) {
            $allowed = in_array($permission, ['list', 'view'], true)
                && $this->isSupplierElementAllowed($element, $user);
        } elseif ($element instanceof Document) {
            $allowed = in_array($permission, ['list', 'view'], true)
                && $this->isSupplierElementAllowed($element, $user);
        }

        $event->setArgument('isAllowed', $allowed);
    }

    private function linkedUser(): ?User
    {
        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->isAdmin()) {
            return null;
        }

        return $this->isDesigner($user) || $this->isSupplier($user) ? $user : null;
    }

    private function isDesigner(User $user): bool
    {
        return $user->isAllowed(self::PERMISSION_DESIGNER);
    }

    private function isSupplier(User $user): bool
    {
        return $user->isAllowed(self::PERMISSION_SUPPLIER);
    }

    private function isObjectAllowed(Concrete $object, User $user): bool
    {
        if ($this->isDesigner($user) && strtolower((string) $object->getClassName()) === 'color') {
            return true;
        }

        if ($this->visibleIdsResolver !== null) {
            $id = (int) $object->getId();
            if ($id > 0) {
                return in_array($id, $this->resolvedTestIds($user), true);
            }

            $parent = $object->getParent();

            return $parent instanceof AbstractObject && in_array((int) $parent->getId(), $this->resolvedTestIds($user), true);
        }

        $context = $this->linkedFamilyContext($user);
        if (!$context['linked']) {
            return false;
        }

        if ($this->isSupplier($user)) {
            return in_array((int) $object->getId(), $context['objectIds'], true);
        }

        $path = (int) $object->getId() > 0
            ? rtrim($object->getRealFullPath(), '/')
            : rtrim((string) $object->getParent()?->getRealFullPath(), '/');

        return $this->pathIsInsideFamilies($path, $context['familyPaths']);
    }

    /** @return list<int> */
    private function resolvedTestIds(User $user): array
    {
        $ids = ($this->visibleIdsResolver)($user);

        return array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
    }

    /**
     * @return array{
     *     linked: bool,
     *     objectIds: list<int>,
     *     familyPaths: list<string>,
     *     navigationIds: list<int>,
     *     assetIds: list<int>,
     *     documentIds: list<int>
     * }
     */
    private function linkedFamilyContext(User $user): array
    {
        $userId = (int) $user->getId();
        if (isset($this->contextByUser[$userId])) {
            return $this->contextByUser[$userId];
        }

        $this->collectingObjects = true;
        try {
            $profileClass = $this->isSupplier($user) ? 'supplier' : 'designer';
            $profile = $this->findLinkedProfile($this->loadObjectsByClass($profileClass), $user);
            if (!$profile instanceof Concrete) {
                return $this->contextByUser[$userId] = $this->emptyContext();
            }

            if ($this->isSupplier($user)) {
                return $this->contextByUser[$userId] = $this->buildSupplierContext($profile);
            }

            $familyPaths = [];
            $navigationIds = [];
            foreach ($this->loadObjectsByClass('family') as $object) {
                if (
                    $object instanceof Concrete
                    && $this->familyContainsProfile($object, $profile, $user)
                ) {
                    $familyPaths[] = rtrim($object->getRealFullPath(), '/');
                    $this->addAncestorIds($object, $navigationIds);
                }
            }

            if ($this->isDesigner($user)) {
                foreach ($this->loadObjectsByClass('color') as $color) {
                    $this->addAncestorIds($color, $navigationIds);
                }
            }

            return $this->contextByUser[$userId] = [
                'linked' => true,
                'objectIds' => [],
                'familyPaths' => array_values(array_unique($familyPaths)),
                'navigationIds' => array_map('intval', array_keys($navigationIds)),
                'assetIds' => [],
                'documentIds' => [],
            ];
        } finally {
            $this->collectingObjects = false;
        }
    }

    /**
     * @return array{
     *     linked: bool,
     *     objectIds: list<int>,
     *     familyPaths: list<string>,
     *     navigationIds: list<int>,
     *     assetIds: list<int>,
     *     documentIds: list<int>
     * }
     */
    private function buildSupplierContext(Concrete $profile): array
    {
        $anchors = [(int) $profile->getId() => $profile];
        foreach ($profile->getDependencies()->getRequiredBy() as $dependency) {
            if (($dependency['type'] ?? null) !== 'object') {
                continue;
            }

            $object = Concrete::getById((int) ($dependency['id'] ?? 0), ['force' => true]);
            if ($object instanceof Concrete) {
                $anchors[(int) $object->getId()] = $object;
            }
        }

        // Relation dependencies are not guaranteed to be indexed on older imports,
        // so query the Family relation table directly through its generated listing.
        foreach ($this->loadFamiliesBySupplier($profile) as $family) {
            $anchors[(int) $family->getId()] = $family;
        }

        $anchorPaths = array_map(
            static fn (Concrete $object): string => rtrim($object->getRealFullPath(), '/'),
            array_values($anchors)
        );
        $relatedIds = array_fill_keys(array_keys($anchors), true);
        $descendants = new ObjectListing();
        $descendants->setUnpublished(true);
        $descendants->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT]);
        [$condition, $parameters] = $this->pathCondition($anchorPaths);
        $descendants->setCondition($condition, $parameters);
        foreach ($descendants->loadIdList() as $id) {
            $relatedIds[(int) $id] = true;
        }

        $navigationIds = [];
        foreach ($anchors as $object) {
            $this->addAncestorFolderIds($object, $navigationIds);
        }

        $folders = new ObjectListing();
        $folders->setUnpublished(true);
        $folders->setObjectTypes([AbstractObject::OBJECT_TYPE_FOLDER]);
        $folders->setCondition($condition, $parameters);
        foreach ($folders->loadIdList() as $id) {
            $navigationIds[(int) $id] = true;
        }

        return [
            'linked' => true,
            'objectIds' => array_map('intval', array_keys($relatedIds)),
            'familyPaths' => [],
            'navigationIds' => array_map('intval', array_keys($navigationIds)),
            'assetIds' => [],
            'documentIds' => [],
        ];
    }

    /**
     * @param list<string> $paths
     * @return array{string, list<string>}
     */
    private function pathCondition(array $paths): array
    {
        $conditions = [];
        $parameters = [];
        foreach ($paths as $path) {
            $conditions[] = '(CONCAT(objects.path, objects.`key`) = ? OR CONCAT(objects.path, objects.`key`) LIKE ?)';
            $parameters[] = $path;
            $parameters[] = $path . '/%';
        }

        return ['(' . implode(' OR ', $conditions ?: ['id = ?']) . ')', $conditions === [] ? ['-1'] : $parameters];
    }

    private function isSupplierElementAllowed(Asset|Document $element, User $user): bool
    {
        // A gallery upload exists before the containing object is saved, so Pimcore has not
        // created the object dependency yet. Let suppliers preview their own uploads while
        // keeping the asset tree hidden and all asset mutation permissions disabled.
        if ($element instanceof Asset && (int) $element->getUserOwner() === (int) $user->getId()) {
            return true;
        }

        if ($this->visibleIdsResolver !== null) {
            return in_array((int) $element->getId(), $this->resolvedTestIds($user), true);
        }

        $objectIds = $this->linkedFamilyContext($user)['objectIds'];
        foreach ($element->getDependencies()->getRequiredBy() as $dependency) {
            if (
                ($dependency['type'] ?? null) === 'object'
                && in_array((int) ($dependency['id'] ?? 0), $objectIds, true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *     linked: false,
     *     objectIds: list<int>,
     *     familyPaths: list<string>,
     *     navigationIds: list<int>,
     *     assetIds: list<int>,
     *     documentIds: list<int>
     * }
     */
    private function emptyContext(): array
    {
        return [
            'linked' => false,
            'objectIds' => [],
            'familyPaths' => [],
            'navigationIds' => [],
            'assetIds' => [],
            'documentIds' => [],
        ];
    }

    /** @return list<Concrete> */
    private function loadObjectsByClass(string $className): array
    {
        $listing = new ObjectListing();
        $listing->setUnpublished(true);
        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT]);
        $listing->setCondition('className = ?', [$className]);

        return array_values(array_filter(
            $listing->load(),
            static fn (mixed $object): bool => $object instanceof Concrete
        ));
    }

    /** @return list<Concrete> */
    private function loadFamiliesBySupplier(Concrete $supplier): array
    {
        $listing = new FamilyListing();
        $listing->setUnpublished(true);
        $listing->filterBySuppliers($supplier);

        return array_values(array_filter(
            $listing->load(),
            static fn (mixed $object): bool => $object instanceof Concrete
        ));
    }

    /**
     * @param list<AbstractObject> $objects
     */
    private function findLinkedProfile(array $objects, User $user): ?Concrete
    {
        foreach ($objects as $object) {
            if (
                $object instanceof Concrete
                && (int) $this->readFieldValue($object, 'pimcoreUser') === (int) $user->getId()
            ) {
                return $object;
            }
        }

        return null;
    }

    /** @param array<int, true> $ids */
    private function addAncestorIds(AbstractObject $object, array &$ids): void
    {
        for ($current = $object; $current instanceof AbstractObject; $current = $current->getParent()) {
            $ids[(int) $current->getId()] = true;
            if ((int) $current->getId() === 1) {
                break;
            }
        }
    }

    /** @param array<int, true> $ids */
    private function addAncestorFolderIds(AbstractObject $object, array &$ids): void
    {
        for ($current = $object->getParent(); $current instanceof AbstractObject; $current = $current->getParent()) {
            if ($current->getType() === AbstractObject::OBJECT_TYPE_FOLDER) {
                $ids[(int) $current->getId()] = true;
            }
            if ((int) $current->getId() === 1) {
                break;
            }
        }
    }

    /** @param list<string> $familyPaths */
    private function pathIsInsideFamilies(string $path, array $familyPaths): bool
    {
        foreach ($familyPaths as $familyPath) {
            if ($path === $familyPath || str_starts_with($path, $familyPath . '/')) {
                return true;
            }
        }

        return false;
    }

    private function familyContainsProfile(Concrete $family, Concrete $profile, User $user): bool
    {
        $fieldName = $this->isSupplier($user) ? 'suppliers' : 'designersRelation';

        return $this->valueContainsObject($this->readFieldValue($family, $fieldName), $profile);
    }

    private function valueContainsObject(mixed $value, Concrete $expected): bool
    {
        if ($value instanceof ObjectMetadata) {
            $value = $value->getObject();
        }
        if ($value instanceof Concrete) {
            return (int) $value->getId() === (int) $expected->getId();
        }
        if (is_iterable($value)) {
            foreach ($value as $item) {
                if ($this->valueContainsObject($item, $expected)) {
                    return true;
                }
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

    /** @param array<string, mixed> $data */
    private function disableWritePermissions(array &$data): void
    {
        foreach (['edit', 'save', 'publish', 'unpublish', 'delete', 'rename', 'create', 'settings', 'properties'] as $permission) {
            $data['permissions'][$permission] = false;
        }
    }

    /** @param array<string, mixed> $data */
    private function setSupplierObjectPermissions(array &$data): void
    {
        foreach (['create', 'delete', 'rename', 'unpublish', 'settings', 'properties'] as $permission) {
            $data['permissions'][$permission] = false;
        }
        foreach (['view', 'list', 'edit', 'save', 'publish'] as $permission) {
            $data['permissions'][$permission] = true;
        }
    }

    private function makeOnlyMediaFieldsEditable(mixed $layout): void
    {
        if (!is_object($layout)) {
            return;
        }
        if ($layout instanceof DataDefinition && method_exists($layout, 'setNoteditable')) {
            $layout->setNoteditable(!$this->isSupplierMediaDefinition($layout));
        }
        if (!method_exists($layout, 'getChildren') || !is_array($layout->getChildren())) {
            return;
        }
        foreach ($layout->getChildren() as $child) {
            $this->makeOnlyMediaFieldsEditable($child);
        }
    }

    /** @return list<string> */
    private function supplierMediaFields(Concrete $object): array
    {
        $fields = [];
        foreach ($object->getClass()->getFieldDefinitions() as $name => $definition) {
            if ($definition instanceof DataDefinition && $this->isSupplierMediaDefinition($definition)) {
                $fields[] = (string) $name;
            }
        }

        return $fields;
    }

    private function isSupplierMediaDefinition(DataDefinition $definition): bool
    {
        if (
            $definition instanceof Image
            || $definition instanceof ImageGallery
            || $definition instanceof Hotspotimage
            || $definition instanceof Video
        ) {
            return true;
        }

        return (
            $definition instanceof ManyToOneRelation
            || $definition instanceof ManyToManyRelation
        ) && $definition->getAssetsAllowed();
    }

    /** @param list<string> $editableFields */
    private function restoreFieldsExcept(Concrete $object, Concrete $persisted, array $editableFields): void
    {
        foreach (array_keys($object->getClass()->getFieldDefinitions()) as $fieldName) {
            if (in_array($fieldName, $editableFields, true)) {
                continue;
            }

            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($object, $setter)) {
                $object->$setter($this->readFieldValue($persisted, $fieldName));
            }
        }
    }

    private function denyLinkingUnrelatedAssets(Concrete $object, Concrete $persisted, User $user): void
    {
        $previousAssetIds = $this->dependencyIds($persisted, 'asset');
        foreach (array_diff($this->dependencyIds($object, 'asset'), $previousAssetIds) as $assetId) {
            $asset = Asset::getById($assetId);
            if (
                !($asset instanceof Asset)
                || (
                    (int) $asset->getUserOwner() !== (int) $user->getId()
                    && !$this->isSupplierElementAllowed($asset, $user)
                )
            ) {
                throw new AccessDeniedHttpException(
                    'Supplier users may only add newly uploaded media or media already associated with their related objects.'
                );
            }
        }
    }

    /** @return list<int> */
    private function dependencyIds(Concrete $object, string $type): array
    {
        $ids = [];
        foreach ($object->resolveDependencies() as $dependency) {
            if (($dependency['type'] ?? null) === $type && (int) ($dependency['id'] ?? 0) > 0) {
                $ids[] = (int) $dependency['id'];
            }
        }

        return array_values(array_unique($ids));
    }
}
