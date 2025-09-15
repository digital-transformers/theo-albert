<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\AdminBundle\Event\DataObject\GetPreSendDataEvent;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;            // add this
use Pimcore\Model\DataObject\Listing as ObjectListing;  

final class OwnObjectsOnlySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageUserResolver $userResolver,
        private array $excludedClasses = [] // optional: global class exclusions by short name
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'pimcore.admin.object.list.beforeListLoad' => 'onBeforeListLoad',
            'pimcore.dataobject.get.preSendData'       => 'onPreSendData',
        ];
    }

    private function getListableWorkspaceRoots(User $user): array
    {
        $roots = [];
        foreach ($user->getWorkspacesObject() ?? [] as $ws) {
            if ($ws->getList()) {
                $p = rtrim((string)$ws->getCpath(), '/');
                $roots[] = $p === '' ? '/' : $p;
            }
        }
        usort($roots, fn($a,$b) => strlen($b) <=> strlen($a));
        return array_values(array_unique($roots));
    }

    public function onBeforeListLoad(GenericEvent $event): void
    {
        $user = $this->userResolver->getUser();
        if (!$user || !$user->isAllowed('see_own_objects_only')) {
            return;
        }

        $list = $event->getSubject();
        // Guard: only act on object listings
        if (!$list instanceof ObjectListing) {
            return;
        }

        $list = $event->getListing();

        // Optional class-based bypass
        $className = $list->getClassName() ?: null;
        if ($className && \in_array((new \ReflectionClass($className))->getShortName(), $this->excludedClasses, true)) {
            return;
        }

        $allowedRoots = $this->getListableWorkspaceRoots($user);

        // If browsing a subtree that is in allowed roots, allow full visibility there
        $parent = $list->getParent();
        if ($parent instanceof AbstractObject) {
            $parentPath = rtrim($parent->getRealFullPath(), '/');
            foreach ($allowedRoots as $root) {
                if ($root === '/' || str_starts_with($parentPath, $root)) {
                    return;
                }
            }
        }

        // Else: user owned OR in allowed roots (for global grid/search)
        $conds  = ['userOwner = ?'];
        $params = [(int)$user->getId()];

        $orParts = [];
        foreach ($allowedRoots as $root) {
            if ($root === '/') {
                return; // root means “everything allowed”
            }
            $exact  = $root;
            $prefix = rtrim($root, '/') . '/%';
            $orParts[] = '(fullpath = ? OR fullpath LIKE ?)';
            $params[] = $exact;
            $params[] = $prefix;
        }

        if ($orParts) {
            $conds[] = '(' . implode(' OR ', $orParts) . ')';
        }

        $list->addConditionParam('(' . implode(' OR ', $conds) . ')', $params);
    }

    public function onPreSendData(GetPreSendDataEvent $event): void
    {
        $user = $this->userResolver->getUser();
        if (!$user || !$user->isAllowed('see_own_objects_only')) {
            return;
        }

        $object = $event->getObject();
        if (!$object instanceof AbstractObject) {
            return;
        }

        // Optional class-based bypass
        $short = (new \ReflectionClass($object))->getShortName();
        if (\in_array($short, $this->excludedClasses, true)) {
            return;
        }

        $allowedRoots = $this->getListableWorkspaceRoots($user);
        $objPath = rtrim($object->getRealFullPath(), '/');
        foreach ($allowedRoots as $root) {
            if ($root === '/' || str_starts_with($objPath, $root)) {
                return;
            }
        }

        if ((int)$object->getUserOwner() !== (int)$user->getId()) {
            $data = $event->getData();
            $data['permissions']['view']    = false;
            $data['permissions']['edit']    = false;
            $data['permissions']['publish'] = false;
            $data['permissions']['delete']  = false;
            $event->setData($data);
        }
    }
}
