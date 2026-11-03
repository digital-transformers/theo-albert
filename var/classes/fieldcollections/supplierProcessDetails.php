<?php

/**
 * Supplier-specific defaults for a manufacturing Process.
 *
 * Fields Summary:
 * - process [manyToOneRelation]
 * - leadTime [numeric]
 * - itemGroup [manyToOneRelation]
 * - workItemGroup [manyToOneRelation]
 * - priceList [manyToOneRelation]
 */

$relation = static function (string $name, string $title, string $class): \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation {
    $field = new \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation();
    $field->setName($name);
    $field->setTitle($title);
    $field->setClasses([['classes' => $class]]);
    $field->setObjectsAllowed(true);
    $field->setAssetsAllowed(false);
    $field->setDocumentsAllowed(false);
    $field->setAllowToClearRelation(true);

    return $field;
};

$leadTime = new \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric();
$leadTime->setName('leadTime');
$leadTime->setTitle('Lead Time');
$leadTime->setInteger(true);
$leadTime->setUnsigned(true);
$leadTime->setMinValue(0);

$panel = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel();
$panel->setName('Base data');
$panel->setTitle('Base data');
$panel->setChildren([
    $relation('process', 'Process', 'process'),
    $leadTime,
    $relation('itemGroup', 'Item Group', 'SAPItemGroup'),
    $relation('workItemGroup', 'Work Item Group', 'SAPItemGroup'),
    $relation('priceList', 'Price List', 'SAPPricelist'),
]);

$root = new \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel();
$root->setChildren([$panel]);

$definition = new \Pimcore\Model\DataObject\Fieldcollection\Definition();
$definition->setKey('supplierProcessDetails');
$definition->setTitle('Supplier Process Details');
$definition->setLayoutDefinitions($root);

return $definition;
