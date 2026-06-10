<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

class ColorOptionsProvider implements SelectOptionsProviderInterface
{
    private static ?array $options = null;

    /**
     * Each option: ['key' => 'CODE - Name', 'value' => <objectId>]
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        if (self::$options !== null) {
            return self::$options;
        }

        $rows = Db::get()->fetchAllAssociative(
            'SELECT `oo_id`, `code`, `name`
             FROM `object_query_color`
             ORDER BY `code` ASC, `name` ASC'
        );

        self::$options = array_map(
            static fn (array $row): array => [
                'key' => trim((string) ($row['code'] ?? '') . ' - ' . (string) ($row['name'] ?? '')),
                'value' => (int) $row['oo_id'],
            ],
            $rows
        );

        return self::$options;
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
