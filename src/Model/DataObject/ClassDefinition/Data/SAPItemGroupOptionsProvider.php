<?php

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\SAPItemGroup;
use Pimcore\Model\DataObject\SAPItemGroup\Listing as SAPItemGroupListing;

class SAPItemGroupOptionsProvider implements SelectOptionsProviderInterface
{
    /**
     * Each option: ['key' => 'Name [code]', 'value' => <objectId>]
     * For SAPItemGroup: 'key' => 'groupName [groupNum]'
     */
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $list = new SAPItemGroupListing();
        // Include drafts in admin if you want:
        // $list->setUnpublished(true);

        // Sort primarily by groupName; adjust if you prefer numeric sort by groupNum
        $list->setOrderKey('groupName');
        $list->setOrder('asc');

        $options = [];
        foreach ($list as $g) {
            /** @var SAPItemGroup $g */
            $name = trim((string)($g->getGroupName() ?? ''));
            $num  = trim((string)($g->getGroupNum() ?? ''));

            // Skip completely empty rows
            if ($name === '' && $num === '') {
                continue;
            }

            // Build "Name [code]" label with graceful fallbacks
            $label = $name !== '' ? $name : '';
            if ($num !== '') {
                $label = $label !== '' ? sprintf('%s [%s]', $label, $num) : sprintf('[%s]', $num);
            }

            $options[] = [
                'key'   => $label,
                'value' => (string)$g->getId(), // store object ID
            ];
        }

        // Safety sort by label
        usort($options, static fn ($a, $b) => strcmp($a['key'], $b['key']));

        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        // Set to false if groups change frequently and you want live loading
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): array|string|null
    {
        return null; // no default
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
