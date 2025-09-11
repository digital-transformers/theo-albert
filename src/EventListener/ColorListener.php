<?php
namespace App\EventListener;

use Pimcore\Model\DataObject\Color;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ColorListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::VALIDATE   => 'onValidate',  // show messages in UI
            DataObjectEvents::PRE_ADD    => 'onPreSave',   // set composite name
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    public function onValidate(DataObjectEvent $e): void
    {
        $obj = $e->getObject();
        if (!$obj instanceof Color) return;

        // include unpublished relations (useful in admin)
        $selected = $obj->getMultiColor(['unpublished' => true]) ?: [];
        if (\count($selected) <= 1) return;

        $offenders = [];
        foreach ($selected as $child) {
            if (!$child instanceof Color) continue;

            // prevent self-reference just in case
            if ($child->getId() === $obj->getId()) {
                $offenders[] = ($child->getName() ?: $child->getCode() ?: $child->getFullPath()) . ' (self reference)';
                continue;
            }

            $childSelected = $child->getMultiColor(['unpublished' => true]) ?: [];
            if (\count($childSelected) > 0) {
                $offenders[] = $child->getName() ?: $child->getCode() ?: $child->getFullPath();
            }
        }

        if ($offenders) {
            throw new ValidationException(sprintf(
                'You cannot add colors that are themselves multi-color. Please remove: %s',
                implode(', ', $offenders)
            ));
        }
    }

    public function onPreSave(DataObjectEvent $e): void
    {
        $obj = $e->getObject();
        if (!$obj instanceof Color) return;

        $selected = $obj->getMultiColor(['unpublished' => true]) ?: [];
        if (\count($selected) <= 1) return;

        $names = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $label = trim((string)($child->geCode() ?: ''));
                if ($label !== '') $names[] = $label;
            }
        }

        if ($names) {
            $compositeName = implode(' + ', $names);
            if ($obj->getCode() !== $compositeName) {
                $obj->setName($compositeName);
            }
        }
    }
}
