<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Model\DataObject\ClassDefinition\Data\ProductClassificationOptionsProvider;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProductClassificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'syncRelations',
            DataObjectEvents::PRE_UPDATE => 'syncRelations',
        ];
    }

    public function syncRelations(DataObjectEvent $event): void
    {
        $object = $event->getObject();
        if (!$object instanceof Concrete || !in_array($object->getClassName(), ['family', 'frame', 'model', 'posMaterialProduct'], true)) {
            return;
        }

        DataObject\Service::useInheritedValues(false, static function () use ($object): void {
            foreach (ProductClassificationOptionsProvider::TYPES as $field => $class) {
                $id = $object->getValueForFieldName($field);
                $target = null;
                if ($id !== null && $id !== '') {
                    $target = ctype_digit((string) $id) ? DataObject::getById((int) $id) : null;
                    if (!$target instanceof $class) {
                        throw new ValidationException(sprintf('Invalid selection for %s.', $field));
                    }
                }
                $object->setValue($field . 'Relation', $target);
            }
        });
    }
}
