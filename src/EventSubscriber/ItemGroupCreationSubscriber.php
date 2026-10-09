<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ItemGroupCreationSubscriber implements EventSubscriberInterface
{
    private const PARENT_CLASS_BY_CHILD = [
        'model' => 'family',
        'frame' => 'model',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreAdd',
        ];
    }

    public function onPreAdd(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        if (!$object instanceof Concrete) {
            return;
        }

        $sourceClass = self::PARENT_CLASS_BY_CHILD[strtolower((string) $object->getClassName())] ?? null;
        if ($sourceClass === null || !method_exists($object, 'getItemGroup') || !method_exists($object, 'setItemGroup')) {
            return;
        }

        if ($this->readLocalItemGroup($object) !== []) {
            return;
        }

        $source = $this->findAncestor($object, $sourceClass);
        if (!$source instanceof Concrete || !method_exists($source, 'getItemGroup')) {
            return;
        }

        $itemGroups = $this->readLocalItemGroup($source);
        if ($itemGroups === []) {
            return;
        }

        $object->setItemGroup($itemGroups);
    }

    /** @return list<object> */
    private function readLocalItemGroup(Concrete $object): array
    {
        $previousInheritedValues = DataObject::getGetInheritedValues();
        DataObject::setGetInheritedValues(false);

        try {
            $itemGroups = $object->getItemGroup();

            return is_array($itemGroups) ? array_values($itemGroups) : [];
        } finally {
            DataObject::setGetInheritedValues($previousInheritedValues);
        }
    }

    private function findAncestor(Concrete $object, string $className): ?Concrete
    {
        $parent = $object->getParent();

        while ($parent instanceof AbstractObject) {
            if ($parent instanceof Concrete && strtolower((string) $parent->getClassName()) === $className) {
                return $parent;
            }

            $parent = $parent->getParent();
        }

        return null;
    }
}
