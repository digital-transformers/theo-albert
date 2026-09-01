<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\BasePricePricingSubscriber;
use App\Service\CommercialPricingGenerator;
use Codeception\Test\Unit;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Frame;

final class BasePricePricingSubscriberTest extends Unit
{
    public function testRunsAfterPermissionSubscribers(): void
    {
        self::assertSame(
            [
                DataObjectEvents::PRE_ADD => ['onPreAdd', -30],
                DataObjectEvents::PRE_UPDATE => ['onPreUpdate', -30],
            ],
            BasePricePricingSubscriber::getSubscribedEvents()
        );
    }

    public function testBasePriceChangeTriggersPricingSynchronization(): void
    {
        $generator = $this->createMock(CommercialPricingGenerator::class);
        $frame = $this->createMock(Frame::class);
        $frame->method('isFieldDirty')->with('basePrice')->willReturn(true);
        $generator->expects(self::once())->method('synchronizeBasePriceChange')->with($frame);

        (new BasePricePricingSubscriber($generator))->onPreUpdate(new DataObjectEvent($frame));
    }

    public function testUnchangedBasePriceDoesNotTriggerSynchronization(): void
    {
        $generator = $this->createMock(CommercialPricingGenerator::class);
        $family = $this->createMock(Family::class);
        $family->method('isFieldDirty')->with('basePrice')->willReturn(false);
        $generator->expects(self::never())->method('synchronizeBasePriceChange');

        (new BasePricePricingSubscriber($generator))->onPreUpdate(new DataObjectEvent($family));
    }
}
