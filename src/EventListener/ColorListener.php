<?php
namespace App\EventListener;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\Element\ValidationException;
use App\Model\DataObject\Color;

final class ColorListener
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD    => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    /**
     * - If multiColor has >1 items:
     *   (1) ensure none of the selected colors is itself a composite (has multiColor set)
     *   (2) set current object's name to the joined names of selected items with " + "
     */
    public function onPreSave(DataObjectEvent $e): void
    {
        $obj = $e->getObject();
        if (!$obj instanceof Color) {
            return;
        }

        $selected = $obj->getMultiColor();
        if (!is_array($selected) || count($selected) <= 1) {
            // Not a composite (or only 1 item) -> do nothing to name
            return;
        }

        // (1) Validation: each selected color must not itself be a composite
        $offenders = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $childSelected = $child->getMultiColor();
                if (is_array($childSelected) && count($childSelected) > 0) {
                    // Prefer showing something recognizable to the editor
                    $offenders[] = $child->getName() ?: $child->getCode() ?: $child->getFullPath();
                }
            }
        }

        if (!empty($offenders)) {
            // This message is shown in the Pimcore backend UI
            $msg = sprintf(
                "You cannot add colors that are themselves multi-color. Please remove: %s",
                implode(', ', $offenders)
            );
            throw new ValidationException($msg);
        }

        // (2) Auto-build the name: join selected item names with " + "
        $names = [];
        foreach ($selected as $child) {
            if ($child instanceof Color) {
                $names[] = trim((string) $child->getName());
            }
        }

        $names = array_filter($names, static fn($n) => $n !== '');
        if (!empty($names)) {
            $compositeName = implode(' + ', $names);
            if ($obj->getName() !== $compositeName) {
                $obj->setName($compositeName);
            }
        }
    }
}
