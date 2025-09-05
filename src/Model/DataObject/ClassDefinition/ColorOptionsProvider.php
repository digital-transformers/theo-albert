<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Color\Listing as ColorListing;

class ColorOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * Return options for Pimcore select fields.
     * Each option: ['key' => 'CODE - Name', 'value' => <objectId>]
     */
    public function getOptions($context, $fieldDefinition)
    {
        $list = new ColorListing();
        // Optional sorting
        $list->setOrderKey('name');
        $list->setOrder('asc');

        $options = [];
        foreach ($list as $color) {
            /** @var Color $color */
            $label = trim(($color->getCode() ?? '') . ' - ' . ($color->getName() ?? ''));
            $options[] = [
                'key'   => $label,
                'value' => $color->getId(), // 👈 object ID
            ];
	}
	dd($options);
        // Guarantee alphabetical order by label
        usort($options, static function ($a, $b) {
            return strcmp($a['key'], $b['key']);
        });

        return $options;
    }

    /**
     * Helper for forms/services: returns ['CODE - Name' => <objectId>, ...]
     */
    public function getOptionsForForm()
    {
        return array_column($this->getOptions(null, null), 'value', 'key');
    }

    public function hasStaticOptions($context, $fieldDefinition)
    {
        // Set to false if colors change often and you want no caching.
        return true;
    }

    public function getDefaultValue($context, $fieldDefinition)
    {
        return null;
    }

    public function getLazyLoading($context, $fieldDefinition)
    {
        return false;
    }

    public function allowMultipleAssignments($context, $fieldDefinition)
    {
        return false;
    }
}

