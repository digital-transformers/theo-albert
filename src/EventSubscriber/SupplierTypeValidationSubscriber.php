<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\SupplierTypeValidator;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SupplierTypeValidationSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_CLASSES = ['family', 'model', 'frame', 'sapcomponent'];

    public function __construct(private readonly SupplierTypeValidator $validator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -20],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -20],
        ];
    }

    /** @throws ValidationException */
    public function onPreSave(DataObjectEvent $event): void
    {
        if ($this->isVersionOnlyOrAutoSave($event)) {
            return;
        }
        $object = $event->getObject();
        if (
            !$object instanceof Concrete
            || !in_array(strtolower((string) $object->getClassName()), self::SUPPORTED_CLASSES, true)
            || !method_exists($object, 'getSuppliers')
        ) {
            return;
        }

        // Do not make an unrelated edit fail because of untouched historical
        // metadata. New objects and explicit supplier-link edits are validated.
        if ($object->getId() > 0 && !$object->isFieldDirty('suppliers')) {
            return;
        }

        $links = $object->getSuppliers(['unpublished' => true]) ?: [];
        $violations = $this->validator->violations(is_array($links) ? $links : []);
        if ($violations !== []) {
            throw new ValidationException(implode(' ', $violations));
        }
    }

    private function isVersionOnlyOrAutoSave(DataObjectEvent $event): bool
    {
        return ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true)
            || ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave') === true);
    }
}
