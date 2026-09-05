<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\CommercialPricingGenerator;
use Doctrine\DBAL\Connection;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Frame;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BasePricePricingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CommercialPricingGenerator $generator,
        private readonly Connection $connection,
    ) {
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
        if (!$this->isPricingObject($object) || $object->getBasePrice() === null) {
            return;
        }

        $this->generator->synchronizeBasePriceChange($object);
    }

    public function onPreUpdate(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        if (!$this->isPricingObject($object) || !$this->basePriceChanged($object)) {
            return;
        }

        $this->generator->synchronizeBasePriceChange($object);
    }

    private function basePriceChanged(Family|Frame $object): bool
    {
        $persisted = $this->connection->fetchOne(
            sprintf('SELECT basePrice FROM object_store_%s WHERE oo_id = ?', $object->getClassId()),
            [$object->getId()]
        );
        $persisted = $persisted === false || $persisted === null ? null : (int) $persisted;
        $current = $object->getBasePrice();
        $current = $current === null ? null : (int) $current;

        return $persisted !== $current;
    }

    private function isPricingObject(mixed $object): bool
    {
        return ($object instanceof Family || $object instanceof Frame)
            && in_array(strtolower((string) $object->getClassName()), ['family', 'frame'], true);
    }
}
