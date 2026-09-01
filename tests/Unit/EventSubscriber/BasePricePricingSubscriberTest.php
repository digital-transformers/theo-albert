<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\BasePricePricingSubscriber;
use App\Service\CommercialPricingGenerator;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
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
        $connection = $this->createMock(Connection::class);
        $frame = $this->createMock(Frame::class);
        $frame->method('getClassId')->willReturn('15');
        $frame->method('getId')->willReturn(123);
        $frame->method('getBasePrice')->willReturn(120);
        $connection->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT basePrice FROM object_store_15 WHERE oo_id = ?', [123])
            ->willReturn('100');
        $generator->expects(self::once())->method('synchronizeBasePriceChange')->with($frame);

        (new BasePricePricingSubscriber($generator, $connection))->onPreUpdate(new DataObjectEvent($frame));
    }

    public function testUnchangedBasePriceDoesNotTriggerSynchronization(): void
    {
        $generator = $this->createMock(CommercialPricingGenerator::class);
        $connection = $this->createMock(Connection::class);
        $family = $this->createMock(Family::class);
        $family->method('getClassId')->willReturn('4');
        $family->method('getId')->willReturn(456);
        $family->method('getBasePrice')->willReturn(100);
        $connection->method('fetchOne')->willReturn('100');
        $generator->expects(self::never())->method('synchronizeBasePriceChange');

        (new BasePricePricingSubscriber($generator, $connection))->onPreUpdate(new DataObjectEvent($family));
    }
}
