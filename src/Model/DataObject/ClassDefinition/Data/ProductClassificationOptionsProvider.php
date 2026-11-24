<?php

declare(strict_types=1);

namespace App\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\BoldnessBar;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ExclusivityIndex;
use Pimcore\Model\DataObject\Line;

final class ProductClassificationOptionsProvider implements SelectOptionsProviderInterface
{
    public const TYPES = [
        'line' => Line::class,
        'boldnessBar' => BoldnessBar::class,
        'exclusivityIndex' => ExclusivityIndex::class,
    ];

    public function getOptions(array $context, Data $fieldDefinition): array
    {
        $class = self::TYPES[$fieldDefinition->getName()];
        $list = $class::getList();
        $list->setUnpublished(false);
        $list->setOrderKey(['name', 'code']);
        $options = [];
        foreach ($list as $item) {
            $options[] = ['key' => $item->getCode() . ' — ' . $item->getName(), 'value' => (string) $item->getId()];
        }

        return $options;
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        return null;
    }
}
