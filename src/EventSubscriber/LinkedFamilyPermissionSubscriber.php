<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Closure;
use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data as DataDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Listing as ObjectListing;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LinkedFamilyPermissionSubscriber implements EventSubscriberInterface
{
    public const PERMISSION_DESIGNER = 'linked_family_designer';
    public const PERMISSION_SUPPLIER = 'linked_family_supplier';

    private const MARKETING_PANEL = 'Marketing';
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

    private bool $collectingObjects = false;

    /** @var array<int, array{linked: bool, familyPaths: list<string>, navigationIds: list<int>}> */
    private array $contextByUser = [];

    private readonly ?Closure $visibleIdsResolver;

    public function __construct(
        private readonly TokenStorageUserResolver $userResolver,
        ?callable $visibleIdsResolver = null,
    ) {
        $this->visibleIdsResolver = $visibleIdsResolver === null ? null : Closure::fromCallable($visibleIdsResolver);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreWrite', -30],
            DataObjectEvents::PRE_UPDATE => ['onPreWrite', -30],
            DataObjectEvents::PRE_DELETE => ['onPreWrite', -30],
            AssetEvents::PRE_ADD => ['onPreWriteAsset', -30],
            AssetEvents::PRE_UPDATE => ['onPreWriteAsset', -30],
            AssetEvents::PRE_DELETE => ['onPreWriteAsset', -30],
            'pimcore.admin.object.list.beforeListLoad' => ['onBeforeListLoad', 20],
            AdminEvents::OBJECT_GET_PRE_SEND_DATA => ['onPreSendData', 20],
            AdminEvents::ASSET_GET_PRE_SEND_DATA => ['onPreSendAssetData', 20],
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

        $conditions = [];
        $parameters = [];
        if ($context['navigationIds'] !== []) {
            $conditions[] = 'id IN (?)';
            $parameters[] = $context['navigationIds'];
        }
        foreach ($context['familyPaths'] as $path) {
            $conditions[] = '(fullpath = ? OR fullpath LIKE ?)';
            $parameters[] = $path;
            $parameters[] = $path . '/%';
        }
        if ($this->isDesigner($user)) {
            $conditions[] = 'className = ?';
            $parameters[] = 'color';
        }

        $list->addConditionParam('(' . implode(' OR ', $conditions ?: ['id = ?']) . ')', $conditions === [] ? [-1] : $parameters);
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
            $data['permissions']['view'] = false;
            $this->disableWritePermissions($data);
            $event->setArgument('data', $data);

            return;
        }

        if (!$this->isSupplier($user)) {
            return;
        }

        $this->disableWritePermissions($data);
        if (isset($data['layout'])) {
            $this->makeAllFieldsReadonly($data['layout']);
            $this->removeLayoutSection($data['layout'], self::MARKETING_PANEL);
        }
        foreach (['data', 'metaData'] as $payloadKey) {
            if (!isset($data[$payloadKey]) || !is_array($data[$payloadKey])) {
                continue;
            }
            foreach (self::MARKETING_FIELDS as $fieldName) {
                unset($data[$payloadKey][$fieldName]);
            }
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
            throw new AccessDeniedHttpException('Supplier users have read-only access to linked families.');
        }

        if (!$this->isObjectAllowed($object, $user)) {
            throw new AccessDeniedHttpException('Designer users may only update linked families, their children, and colors.');
        }
    }

    public function onPreWriteAsset(AssetEvent $event): void
    {
        $user = $this->linkedUser();
        if ($user instanceof User && $this->isSupplier($user)) {
            throw new AccessDeniedHttpException('Supplier users have read-only asset access.');
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

        foreach (['publish', 'delete', 'rename', 'create', 'settings', 'properties'] as $permission) {
            $data['userPermissions'][$permission] = false;
        }
        $data['userPermissions']['list'] = true;
        $data['userPermissions']['view'] = true;
        $event->setArgument('data', $data);
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

    /** @return array{linked: bool, familyPaths: list<string>, navigationIds: list<int>} */
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
                return $this->contextByUser[$userId] = ['linked' => false, 'familyPaths' => [], 'navigationIds' => []];
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
                'familyPaths' => array_values(array_unique($familyPaths)),
                'navigationIds' => array_map('intval', array_keys($navigationIds)),
            ];
        } finally {
            $this->collectingObjects = false;
        }
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

    private function makeAllFieldsReadonly(mixed $layout): void
    {
        if (!is_object($layout)) {
            return;
        }
        if ($layout instanceof DataDefinition && method_exists($layout, 'setNoteditable')) {
            $layout->setNoteditable(true);
        }
        if (!method_exists($layout, 'getChildren') || !is_array($layout->getChildren())) {
            return;
        }
        foreach ($layout->getChildren() as $child) {
            $this->makeAllFieldsReadonly($child);
        }
    }

    private function removeLayoutSection(mixed $layout, string $sectionName): void
    {
        if (!is_object($layout) || !method_exists($layout, 'getChildren') || !method_exists($layout, 'setChildren')) {
            return;
        }
        $children = $layout->getChildren();
        if (!is_array($children)) {
            return;
        }

        $retained = [];
        foreach ($children as $child) {
            if (is_object($child) && method_exists($child, 'getName') && (string) $child->getName() === $sectionName) {
                continue;
            }
            $this->removeLayoutSection($child, $sectionName);
            $retained[] = $child;
        }
        $layout->setChildren($retained);
    }
}
