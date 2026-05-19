<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PhotographerAssetPermissionSubscriber implements EventSubscriberInterface
{
    private const ROLE_NAME = 'Photographer';

    public function __construct(
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::PRE_ADD => 'onPreAdd',
        ];
    }

    public function onPreAdd(AssetEvent $event): void
    {
        if (!$event->getAsset() instanceof Folder) {
            return;
        }

        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->isAdmin() || !$this->hasPhotographerRole($user)) {
            return;
        }

        throw new AccessDeniedHttpException('Photographer users may upload files, but cannot create asset folders.');
    }

    private function hasPhotographerRole(User $user): bool
    {
        $role = Role::getByName(self::ROLE_NAME);

        return $role instanceof Role && in_array((int) $role->getId(), $user->getRoles(), true);
    }
}
