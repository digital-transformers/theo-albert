<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ProductPermissionSubscriber;
use Codeception\Test\Unit;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ProductPermissionSubscriberTest extends Unit
{
    public function testMarketingUserOnlyEditsMarketingFields(): void
    {
        $subscriber = new ProductPermissionSubscriber($this->marketingUserResolver());
        $baseField = (new Input())->setName('name');
        $qualityControlField = (new Input())->setName('qualityControlRemarks');
        $marketing = (new Panel())
            ->setName('Marketing')
            ->setChildren([
                (new Input())->setName('imageGallery'),
                (new Input())->setName('unexpectedField'),
            ]);
        $layout = (new Panel())
            ->setName('root')
            ->setChildren([
                (new Panel())->setName('Base data')->setChildren([$baseField]),
                $marketing,
                (new Panel())->setName('Quality Control')->setChildren([$qualityControlField]),
            ]);
        $event = new GenericEvent(null, [
            'object' => (new ProductPermissionTestObject())->setClassName('model'),
            'data' => ['layout' => $layout, 'permissions' => ['edit' => true]],
        ]);

        $subscriber->onPreSendData($event);

        self::assertSame(['Base data', 'Marketing', 'Quality Control'], array_map(
            static fn (Panel $panel): string => $panel->getName(),
            $layout->getChildren()
        ));
        self::assertTrue($baseField->getNoteditable());
        self::assertFalse($marketing->getChildren()[0]->getNoteditable());
        self::assertTrue($marketing->getChildren()[1]->getNoteditable());
        self::assertTrue($qualityControlField->getNoteditable());
    }

    public function testSubscriberUsesCurrentPimcoreAdminObjectEvent(): void
    {
        self::assertArrayHasKey(
            'pimcore.admin.dataobject.get.preSendData',
            ProductPermissionSubscriber::getSubscribedEvents()
        );
    }

    public function testKeyReadonlyUserCannotEditAnyObjectField(): void
    {
        $subscriber = new ProductPermissionSubscriber($this->keyReadonlyUserResolver());
        $field = (new Input())->setName('name');
        $layout = (new Panel())->setName('root')->setChildren([$field]);
        $event = new GenericEvent(null, [
            'object' => (new ProductPermissionTestObject())->setClassName('supplier'),
            'data' => ['layout' => $layout, 'permissions' => ['edit' => true, 'publish' => true]],
        ]);

        $subscriber->onPreSendData($event);

        self::assertTrue($field->getNoteditable());
        self::assertFalse($event->getArgument('data')['permissions']['edit']);
        self::assertFalse($event->getArgument('data')['permissions']['publish']);
    }

    public function testKeyReadonlyAssetPermissionsOnlyAllowViewing(): void
    {
        $subscriber = new ProductPermissionSubscriber($this->keyReadonlyUserResolver());
        $event = new GenericEvent(null, [
            'asset' => new Image(),
            'data' => ['userPermissions' => ['view' => true, 'publish' => true, 'delete' => true]],
        ]);

        $subscriber->onPreSendAssetData($event);

        $permissions = $event->getArgument('data')['userPermissions'];
        self::assertTrue($permissions['view']);
        self::assertTrue($permissions['list']);
        self::assertFalse($permissions['publish']);
        self::assertFalse($permissions['delete']);
    }

    public function testMarketingUserCannotEditUnsupportedObject(): void
    {
        $subscriber = new ProductPermissionSubscriber($this->marketingUserResolver());
        $event = new GenericEvent(null, [
            'object' => (new ProductPermissionTestObject())->setClassName('supplier'),
            'data' => ['permissions' => ['edit' => true, 'publish' => true]],
        ]);

        $subscriber->onPreSendData($event);

        $permissions = $event->getArgument('data')['permissions'];
        self::assertFalse($permissions['edit']);
        self::assertFalse($permissions['publish']);
        self::assertFalse($permissions['delete']);
    }

    public function testMarketingUserSaveOfUnsupportedObjectIsDenied(): void
    {
        $subscriber = new ProductPermissionSubscriber($this->marketingUserResolver());

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreSave(new DataObjectEvent(
            (new ProductPermissionTestObject())->setClassName('supplier')
        ));
    }

    private function marketingUserResolver(): TokenStorageUserResolver
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('marketing-user')
                ->setPermissions([ProductPermissionSubscriber::PERMISSION_MARKETING_ONLY])
                ->setAdmin(false)
        );

        return $resolver;
    }

    private function keyReadonlyUserResolver(): TokenStorageUserResolver
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('key-readonly-user')
                ->setPermissions([ProductPermissionSubscriber::PERMISSION_KEY_READONLY])
                ->setAdmin(false)
        );

        return $resolver;
    }
}

final class ProductPermissionTestObject extends Concrete
{
}
