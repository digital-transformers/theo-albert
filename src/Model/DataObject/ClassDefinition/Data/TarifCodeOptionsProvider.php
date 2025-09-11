<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\TarifCode;
use Pimcore\Model\DataObject\TarifCode\Listing as TarifCodeListing;

class TarifCodeOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * Each option: ['key' => 'Name [code]', 'value' => <objectId>]
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $list = new TarifCodeListing();
        // Only published by default; setUnpublished(true) if you need drafts too
        $list->setOrderKey('name');
        $list->setOrder('asc');

        $options = [];
        foreach ($list as $tc) {
            /** @var TarifCode $tc */
            $name = trim((string)($tc->getName() ?? ''));
            $code = trim((string)($tc->getCode() ?? ''));

            // Skip completely empty rows
            if ($name === '' && $code === '') {
                continue;
            }

            // Prefer "Name [code]"; if name missing, show just "[code]"
            $label = $name !== '' ? $name : '';
            if ($code !== '') {
                $label = $label !== '' ? sprintf('%s [%s]', $label, $code) : sprintf('[%s]', $code);
            }

            $options[] = [
                'key'   => $label,
                'value' => (string)$tc->getId(), // use object ID as the stored value
            ];
        }

        // Secondary safety sort by label
        usort($options, static fn ($a, $b) => strcmp($a['key'], $b['key']));
        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        // Set to false if TarifCodes change frequently and you want live loading
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): array|string|null
    {
        // Return a single ID (string), array of IDs for multiselect, or null
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
}
