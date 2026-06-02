<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class PhotographerAssetPermissionSubscriber implements EventSubscriberInterface
{
    private const ROLE_NAMES = ['Photographer', 'Pictures'];
    private const ALLOWED_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png', 'tif', 'tiff', 'webp', 'zip'];

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
        $asset = $event->getAsset();
        if (!$asset instanceof Asset) {
            return;
        }

        $user = $this->userResolver->getUser();
        if (!$user instanceof User || $user->isAdmin() || !$this->hasPicturesRole($user)) {
            return;
        }

        if ($asset instanceof Folder) {
            throw new AccessDeniedHttpException('Pictures users may upload files, but cannot create asset folders.');
        }

        $extension = strtolower(pathinfo((string) $asset->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new AccessDeniedHttpException('Pictures users may only upload images or ZIP files.');
        }
    }

    private function hasPicturesRole(User $user): bool
    {
        foreach (self::ROLE_NAMES as $roleName) {
            $role = Role::getByName($roleName);
            if ($role instanceof Role && in_array((int) $role->getId(), $user->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }
}
