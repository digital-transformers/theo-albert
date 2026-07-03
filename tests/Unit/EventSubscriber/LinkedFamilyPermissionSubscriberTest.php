<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LinkedFamilyPermissionSubscriber;
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

final class LinkedFamilyPermissionSubscriberTest extends Unit
{
    public function testSupplierHasReadonlyDataWithoutMarketingLayout(): void
    {
        $field = (new Input())->setName('name');
        $layout = (new Panel())->setName('root')->setChildren([
            (new Panel())->setName('Base data')->setChildren([$field]),
            (new Panel())->setName('Marketing')->setChildren([(new Input())->setName('imageGallery')]),
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

        self::assertSame(['Base data'], array_map(static fn (Panel $panel): string => $panel->getName(), $layout->getChildren()));
        self::assertTrue($field->getNoteditable());
        self::assertFalse($event->getArgument('data')['permissions']['edit']);
        self::assertArrayNotHasKey('imageGallery', $event->getArgument('data')['data']);
    }

    public function testSupplierCannotWriteLinkedObject(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);

        $this->expectException(AccessDeniedHttpException::class);
        $subscriber->onPreWrite(new DataObjectEvent(
            (new LinkedFamilyTestObject())->setId(10)->setClassName('family')
        ));
    }

    public function testSupplierAssetPermissionsAreReadonly(): void
    {
        $subscriber = $this->subscriber($this->user(LinkedFamilyPermissionSubscriber::PERMISSION_SUPPLIER), [10]);
        $event = new GenericEvent(null, [
            'asset' => new Image(),
            'data' => ['userPermissions' => ['view' => true, 'publish' => true, 'delete' => true]],
        ]);

        $subscriber->onPreSendAssetData($event);

        self::assertTrue($event->getArgument('data')['userPermissions']['view']);
        self::assertFalse($event->getArgument('data')['userPermissions']['publish']);
        self::assertFalse($event->getArgument('data')['userPermissions']['delete']);
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

    private function subscriber(User $user, array $ids): LinkedFamilyPermissionSubscriber
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn($user);

        return new LinkedFamilyPermissionSubscriber($resolver, static fn (User $resolvedUser): array => $ids);
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
