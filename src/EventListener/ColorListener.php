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

    /**
     * Rules:
     * 1) If multiColor has > 1 items, none of those items may be composite themselves.
     * 2) If multiColor has > 1 items, set current name = joined child names with " + ".
     */
    public function onPreSave(DataObjectEvent $e): void
    {
        $obj = $e->getObject();
        if (!$obj instanceof Color) {
            return;
        }

        $selected = $obj->getMultiColor();
        if (!\is_array($selected) || \count($selected) <= 1) {
            // 0 or 1 item -> no validation, no auto-name
            return;
        }

        // (1) Validate: children cannot themselves have multiColor
        $offenders = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $childSelected = $child->getMultiColor();
                if (\is_array($childSelected) && \count($childSelected) > 0) {
                    $offenders[] = $child->getName() ?: $child->getCode() ?: $child->getFullPath();
                }
            }
        }

        if ($offenders) {
            throw new ValidationException(
                sprintf(
                    'You cannot add colors that are themselves multi-color. Please remove: %s',
                    implode(', ', $offenders)
                )
            );
        }

        // (2) Auto-build composite name as "A + B + C"
        $names = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $label = trim((string)($child->getName() ?: $child->getCode() ?: ''));
                if ($label !== '') {
                    $names[] = $label;
                }
            }
        }
        if ($names) {
            $compositeName = implode(' + ', $names);
            dd($compositeName);
            if ($obj->getName() !== $compositeName) {
                $obj->setName($compositeName);
            }
        }
    }
}
