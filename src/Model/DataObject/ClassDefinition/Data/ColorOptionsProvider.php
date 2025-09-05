<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Color\Listing as ColorListing;

class ColorOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * Each option: ['key' => 'CODE - Name', 'value' => <objectId>]
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $list = new ColorListing();
        $list->setOrderKey('name');
        $list->setOrder('asc');

        $options = [];
        foreach ($list as $color) {
            /** @var Color $color */
            $label = trim(($color->getCode() ?? '') . ' - ' . ($color->getName() ?? ''));
            $options[] = [
                'key'   => $label,
                'value' => $color->getId(), // object ID
            ];
        }

        usort($options, static fn($a, $b) => strcmp($a['key'], $b['key']));
        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true; // set false if colors change frequently
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): array|string|null
    {
        // return an object ID (string) / array of IDs for multiselect / or null
        return null;
    }

    public function getLazyLoading(array $context, Data $fieldDefinition): bool
    {
        return false;
    }

    public function allowMultipleAssignments(array $context, Data $fieldDefinition): bool
    {
        return false;
    }

    /** Helper (not part of the interface) */
    public function getOptionsForForm(): array
    {
        return array_column($this->getOptions([], new Data()), 'value', 'key');
    }
}

