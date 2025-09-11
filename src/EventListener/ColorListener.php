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
            DataObjectEvents::PRE_ADD    => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    public function onPreSave(DataObjectEvent $e): void
    {
        $obj = $e->getObject();
        if (!$obj instanceof Color) {
            return;
        }

        // include unpublished relations in admin context
        $selected = $obj->getMultiColor(['unpublished' => true]) ?: [];
        if (\count($selected) <= 1) {
            return; // nothing to validate / build
        }

        // (1) Validate: none of the selected children can itself be "composite"
        // If you actually mean "must not have MORE THAN ONE", change > 0 to > 1
        $offenders = [];
        foreach ($selected as $child) {
            if (!$child instanceof Color) {
                continue;
            }

            // avoid accidental self-reference
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
            dd($offenders);
            // This exception is shown in the Pimcore backend UI on save
            throw new ValidationException(sprintf(
                'You cannot add colors that are themselves multi-color. Please remove: %s',
                implode(', ', $offenders)
            ));
        }

        // (2) Auto-build composite name as "A + B + C"
        $names = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $label = trim((string)($child->getCode() ?: ''));
                if ($label !== '') {
                    $names[] = $label;
                }
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
