<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\CommercialPricingGenerator;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Frame;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BasePricePricingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CommercialPricingGenerator $generator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreAdd', -30],
            DataObjectEvents::PRE_UPDATE => ['onPreUpdate', -30],
        ];
    }

    public function onPreAdd(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        if ((!$object instanceof Family && !$object instanceof Frame) || $object->getBasePrice() === null) {
            return;
        }

        $this->generator->synchronizeBasePriceChange($object);
    }

    public function onPreUpdate(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        if ((!$object instanceof Family && !$object instanceof Frame) || !$object->isFieldDirty('basePrice')) {
            return;
        }

        $this->generator->synchronizeBasePriceChange($object);
    }
}
