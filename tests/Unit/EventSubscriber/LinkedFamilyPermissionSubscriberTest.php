<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LinkedFamilyPermissionSubscriber;
use App\Service\AutomaticAssetMoveGuard;
use Codeception\Test\Unit;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image as ImageField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class LinkedFamilyPermissionSubscriberTest extends Unit
{
    public function testSupplierCanEditOnlyMediaFieldsOnRelatedObject(): void
    {
        $field = (new Input())->setName('name');
        $imageField = (new ImageField())->setName('imageGallery');
        $layout = (new Panel())->setName('root')->setChildren([
            (new Panel())->setName('Base data')->setChildren([$field]),
            (new Panel())->setName('Marketing')->setChildren([$imageField]),
        ]);
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);
        $event = new GenericEvent(null, [
            'object' => (new LinkedFamilyTestObject())->setId(10)->setClassName('family'),
            'data' => [
                'layout' => $layout,
                'permissions' => ['edit' => true, 'publish' => true],
                'data' => ['name' => 'Visible', 'imageGallery' => ['hidden']],
            ],
        ]);

        $subscriber->onPreSendData($event);

        self::assertSame(['Base data', 'Marketing'], array_map(static fn (Panel $panel): string => $panel->getName(), $layout->getChildren()));
        self::assertTrue($field->getNoteditable());
        self::assertFalse($imageField->getNoteditable());
        self::assertTrue($event->getArgument('data')['permissions']['edit']);
        self::assertArrayHasKey('imageGallery', $event->getArgument('data')['data']);
    }

    public function testSupplierCannotWriteUnrelatedObject(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreWrite(new DataObjectEvent(
            (new LinkedFamilyTestObject())->setId(20)->setClassName('family')
        ));
    }

    public function testSupplierAssetPermissionsAreReadonly(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);
        $event = new GenericEvent(null, [
            'asset' => (new Image())->setId(10),
            'data' => ['userPermissions' => ['view' => true, 'publish' => true, 'delete' => true]],
        ]);

        $subscriber->onPreSendAssetData($event);

        self::assertTrue($event->getArgument('data')['userPermissions']['view']);
        self::assertFalse($event->getArgument('data')['userPermissions']['publish']);
        self::assertFalse($event->getArgument('data')['userPermissions']['delete']);
    }

    public function testSupplierCannotOpenUnrelatedAsset(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);
        $event = new GenericEvent(null, [
            'asset' => (new Image())->setId(20),
            'data' => ['userPermissions' => ['view' => true]],
        ]);

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreSendAssetData($event);
    }

    public function testSupplierCanUploadMediaButCannotCreateAssetFolders(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);
        $subscriber->onPreAddAsset(new AssetEvent(new Image()));

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreAddAsset(new AssetEvent(new AssetFolder()));
    }

    public function testSupplierAssetUpdateIsAllowedOnlyDuringAutomaticOrganization(): void
    {
        $user = $this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER);
        $guard = new AutomaticAssetMoveGuard();
        $subscriber = $this->subscriber($user, [10], $guard);
        $event = new AssetEvent((new Image())->setUserOwner(5));

        $guard->run(static fn (): mixed => $subscriber->onPreWriteAsset($event));

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreWriteAsset($event);
    }

    public function testSupplierFolderCreationIsAllowedOnlyDuringAutomaticOrganization(): void
    {
        $user = $this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER);
        $guard = new AutomaticAssetMoveGuard();
        $subscriber = $this->subscriber($user, [10], $guard);
        $event = new AssetEvent(new AssetFolder());

        $guard->run(static fn (): mixed => $subscriber->onPreAddAsset($event));

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreAddAsset($event);
    }

    public function testSupplierElementPermissionsHideAssetFolders(): void
    {
        $user = $this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER);
        $subscriber = $this->subscriber($user, [10]);
        $linkedAssetView = new ElementEvent((new Image())->setId(10), [
            'user' => $user,
            'permissionType' => 'view',
            'isAllowed' => false,
        ]);
        $unrelatedAssetView = new ElementEvent((new Image())->setId(20), [
            'user' => $user,
            'permissionType' => 'view',
            'isAllowed' => true,
        ]);
        $ownUploadView = new ElementEvent((new Image())->setId(30)->setUserOwner(5), [
            'user' => $user,
            'permissionType' => 'view',
            'isAllowed' => false,
        ]);
        $folderCreate = new ElementEvent(new AssetFolder(), [
            'user' => $user,
            'permissionType' => 'create',
            'isAllowed' => false,
        ]);
        $folderView = new ElementEvent(new AssetFolder(), [
            'user' => $user,
            'permissionType' => 'view',
            'isAllowed' => true,
        ]);

        foreach ([$linkedAssetView, $unrelatedAssetView, $ownUploadView, $folderCreate, $folderView] as $event) {
            $subscriber->onElementPermissionIsAllowed($event);
        }

        self::assertTrue($linkedAssetView->getArgument('isAllowed'));
        self::assertFalse($unrelatedAssetView->getArgument('isAllowed'));
        self::assertTrue($ownUploadView->getArgument('isAllowed'));
        self::assertTrue($folderCreate->getArgument('isAllowed'));
        self::assertFalse($folderView->getArgument('isAllowed'));
    }

    public function testDesignerCanWriteLinkedObjectAndColorsButNotOtherFamilies(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_DESIGNER), [10]);
        $subscriber->onPreWrite(new DataObjectEvent(
            (new LinkedFamilyTestObject())->setId(10)->setClassName('model')
        ));
        $subscriber->onPreWrite(new DataObjectEvent(
            (new LinkedFamilyTestObject())->setClassName('color')
        ));

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreWrite(new DataObjectEvent(
            (new LinkedFamilyTestObject())->setId(20)->setClassName('family')
        ));
    }

    private function subscriber(
        User $user,
        array $ids,
        ?AutomaticAssetMoveGuard $automaticAssetMoveGuard = null,
    ): LinkedFamilyPermissionSubscriber
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn($user);

        return new LinkedFamilyPermissionSubscriber(
            $resolver,
            $automaticAssetMoveGuard ?? new AutomaticAssetMoveGuard(),
            static fn (User $resolvedUser): array => $ids
        );
    }

    private function user(string $permission): User
    {
        return (new User())
            ->setId(5)
            ->setUsername('linked-user')
            ->setPermissions([$permission])
            ->setAdmin(false);
    }
}

final class LinkedFamilyTestObject extends Concrete
{
}
