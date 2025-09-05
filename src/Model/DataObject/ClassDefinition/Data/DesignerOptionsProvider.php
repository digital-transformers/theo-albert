<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\Designer;
use Pimcore\Model\DataObject\Designer\Listing as DesignerListing;

class DesignerOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * Returns options like:
     *   ['key' => 'Rossi Mario', 'value' => <designerId>]
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $list = new DesignerListing();
        // adjust if your field is named differently (e.g., 'surname')
        $list->setOrderKey('lastName');
        $list->setOrder('asc');

        $options = [];
        foreach ($list as $designer) {
            /** @var Designer $designer */
            $ln = trim((string) $designer->getLastName());
            $fn = trim((string) $designer->getFirstName());
            $label = trim($ln . ' ' . $fn);

            $options[] = [
                'key'   => $label !== '' ? $label : ('#' . $designer->getId()),
                'value' => $designer->getId(), // object ID
            ];
        }

        // Safety: ensure alphabetical by label even if DB collation differs
        usort($options, static fn($a, $b) => strcmp($a['key'], $b['key']));

        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        // Set to false if designers change often and you don't want caching
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): array|string|null
    {
        // Return an ID (string) or null; for multiselect return array of IDs
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

    /** Optional helper for forms/services (not part of the interface) */
    public function getOptionsForForm(): array
    {
        return array_column($this->getOptions([], new Data()), 'value', 'key');
    }
}

