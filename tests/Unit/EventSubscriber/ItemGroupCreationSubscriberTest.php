<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ItemGroupCreationSubscriber;
use Codeception\Test\Unit;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;

final class ItemGroupCreationSubscriberTest extends Unit
{
    public function testNewModelCopiesItemGroupsFromFamily(): void
    {
        $groups = [new \stdClass(), new \stdClass()];
        $family = (new ItemGroupCreationTestObject())->setClassName('family')->setItemGroup($groups);
        $model = (new ItemGroupCreationTestObject())->setClassName('model')->setTestParent($family);

        (new ItemGroupCreationSubscriber())->onPreAdd(new DataObjectEvent($model));

        self::assertSame($groups, $model->getItemGroup());
    }

    public function testNewFrameCopiesItemGroupsFromModel(): void
    {
        $groups = [new \stdClass()];
        $family = (new ItemGroupCreationTestObject())->setClassName('family');
        $model = (new ItemGroupCreationTestObject())->setClassName('model')->setItemGroup($groups)->setTestParent($family);
        $frame = (new ItemGroupCreationTestObject())->setClassName('frame')->setTestParent($model);

        (new ItemGroupCreationSubscriber())->onPreAdd(new DataObjectEvent($frame));

        self::assertSame($groups, $frame->getItemGroup());
    }

    public function testExplicitChildItemGroupsArePreserved(): void
    {
        $parentGroups = [new \stdClass()];
        $childGroups = [new \stdClass()];
        $family = (new ItemGroupCreationTestObject())->setClassName('family')->setItemGroup($parentGroups);
        $model = (new ItemGroupCreationTestObject())
            ->setClassName('model')
            ->setItemGroup($childGroups)
            ->setTestParent($family);

        (new ItemGroupCreationSubscriber())->onPreAdd(new DataObjectEvent($model));

        self::assertSame($childGroups, $model->getItemGroup());
    }

    public function testUpdatesAreNotSubscribed(): void
    {
        self::assertSame(
            ['pimcore.dataobject.preAdd' => 'onPreAdd'],
            ItemGroupCreationSubscriber::getSubscribedEvents()
        );
    }
}

final class ItemGroupCreationTestObject extends Concrete
{
    /** @var list<object> */
    private array $itemGroup = [];
    private ?self $testParent = null;

    /** @return list<object> */
    public function getItemGroup(): array
    {
        return $this->itemGroup;
    }

    /** @param list<object> $itemGroup */
    public function setItemGroup(?array $itemGroup): static
    {
        $this->itemGroup = $itemGroup ?? [];

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->testParent;
    }

    public function setTestParent(?self $testParent): static
    {
        $this->testParent = $testParent;

        return $this;
    }
}
