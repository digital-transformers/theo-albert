<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\PhotographerAssetPermissionSubscriber;
use App\Service\AutomaticAssetMoveGuard;
use Codeception\Test\Unit;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;

final class PhotographerAssetPermissionSubscriberTest extends Unit
{
    public function testAutomaticMarketingFolderCreationBypassesRoleGuard(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())->setId(5)->setUsername('pictures-user')->setAdmin(false)
        );
        $guard = new AutomaticAssetMoveGuard();
        $subscriber = new PhotographerAssetPermissionSubscriber($resolver, $guard);

        $guard->run(static fn (): mixed => $subscriber->onPreAdd(new AssetEvent(new Folder())));

        self::assertFalse($guard->isActive());
    }
}
