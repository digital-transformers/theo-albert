<?php

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - code [input]
 * - name [input]
 * - sapStep [select]
 * - order [numeric]
 * - create [checkbox]
 * - itemGroup [manyToOneRelation]
 * - workItemGroup [manyToOneRelation]
 * - leadTimeMetalSingleColor [numeric]
 * - leadTimeMetalBiColor [numeric]
 * - leadTimeAcetate [numeric]
 * - leadTimeMetalAcetate [numeric]
 */

$processNumeric = static function (string $name, string $title): \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric {
    $field = new \Pimcore\Model\DataObject\ClassDefinition\Data\Numeric();
    $field->setName($name);
    $field->setTitle($title);
    $field->setInteger(true);
    $field->setUnsigned(true);
    $field->setMinValue(0);

    return $field;
};

$processRelation = static function (string $name, string $title): \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation {
    $field = new \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation();
    $field->setName($name);
    $field->setTitle($title);
    $field->setClasses([['classes' => 'SAPItemGroup']]);
    $field->setObjectsAllowed(true);
    $field->setAssetsAllowed(false);
    $field->setDocumentsAllowed(false);
    $field->setAllowToClearRelation(true);

    return $field;
};

return \Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'dao' => NULL,
   'id' => 'process',
   'name' => 'process',
   'title' => '',
   'description' => '',
   'creationDate' => NULL,
   'modificationDate' => 1757555958,
   'userOwner' => 2,
   'userModification' => 2,
   'parentClass' => '',
   'implementsInterfaces' => '',
   'listingParentClass' => '',
   'useTraits' => '',
   'listingUseTraits' => '',
   'encryption' => false,
   'encryptedTables' => 
  array (
  ),
   'allowInherit' => false,
   'allowVariants' => false,
   'showVariants' => false,
   'layoutDefinitions' => 
  \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
     'name' => 'pimcore_root',
     'type' => NULL,
     'region' => NULL,
     'title' => NULL,
     'width' => 0,
     'height' => 0,
     'collapsible' => false,
     'collapsed' => false,
     'bodyStyle' => NULL,
     'datatype' => 'layout',
     'children' => 
    array (
      0 => 
      \Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
         'name' => 'Base data',
         'type' => NULL,
         'region' => NULL,
         'title' => 'Base data',
         'width' => '',
         'height' => '',
         'collapsible' => false,
         'collapsed' => false,
         'bodyStyle' => '',
         'datatype' => 'layout',
         'children' => 
        array (
          0 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'code',
             'title' => 'Code',
             'tooltip' => '',
             'mandatory' => true,
             'noteditable' => false,
             'index' => true,
             'locked' => false,
             'style' => '',
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => false,
             'visibleSearch' => false,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => true,
             'showCharCount' => false,
             'width' => '',
             'defaultValueGenerator' => '',
          )),
          1 => 
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'name',
             'title' => 'Name',
             'tooltip' => '',
             'mandatory' => true,
             'noteditable' => false,
             'index' => false,
             'locked' => false,
             'style' => '',
             'permissions' => NULL,
             'fieldtype' => '',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => false,
             'visibleSearch' => false,
             'blockedVarsForExport' => 
            array (
            ),
             'defaultValue' => NULL,
             'columnLength' => 190,
             'regex' => '',
             'regexFlags' => 
            array (
            ),
             'unique' => false,
             'showCharCount' => false,
             'width' => '',
             'defaultValueGenerator' => '',
          )),
          2 => (static function (): \Pimcore\Model\DataObject\ClassDefinition\Data\Select {
              $field = new \Pimcore\Model\DataObject\ClassDefinition\Data\Select();
              $field->setName('sapStep');
              $field->setTitle('SAP Step');
              $field->setOptions([
                  ['key' => 'BRUT / AF', 'value' => 'BRUT_AF'],
                  ['key' => 'Color', 'value' => 'COLOR'],
                  ['key' => 'Quality', 'value' => 'QUALITY'],
              ]);
              $field->setEnforceValidation(true);

              return $field;
          })(),
          3 => $processNumeric('order', 'Order'),
          4 => (static function (): \Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox {
              $field = new \Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox();
              $field->setName('create');
              $field->setTitle('Create');
              $field->setDefaultValue(1);

              return $field;
          })(),
          5 => $processRelation('itemGroup', 'Item Group'),
          6 => $processRelation('workItemGroup', 'Work Item Group'),
          7 => $processNumeric('leadTimeMetalSingleColor', 'Lead Time - Metal Single Color'),
          8 => $processNumeric('leadTimeMetalBiColor', 'Lead Time - Metal Bi-Color'),
          9 => $processNumeric('leadTimeAcetate', 'Lead Time - Acetate'),
          10 => $processNumeric('leadTimeMetalAcetate', 'Lead Time - Metal-Acetate'),
        ),
         'locked' => false,
         'blockedVarsForExport' => 
        array (
        ),
         'fieldtype' => 'panel',
         'layout' => NULL,
         'border' => false,
         'icon' => '',
         'labelWidth' => 100,
         'labelAlign' => 'left',
      )),
    ),
     'locked' => false,
     'blockedVarsForExport' => 
    array (
    ),
     'fieldtype' => 'panel',
     'layout' => NULL,
     'border' => false,
     'icon' => NULL,
     'labelWidth' => 100,
     'labelAlign' => 'left',
  )),
   'icon' => '/bundles/pimcoreadmin/img/flat-color-icons/settings.svg',
   'group' => '',
   'showAppLoggerTab' => false,
   'linkGeneratorReference' => '',
   'previewGeneratorReference' => '',
   'compositeIndices' => 
  array (
  ),
   'showFieldLookup' => false,
   'propertyVisibility' => 
  array (
    'grid' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
    'search' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
  ),
   'enableGridLocking' => false,
   'deletedDataComponents' => 
  array (
  ),
   'blockedVarsForExport' => 
  array (
  ),
   'fieldDefinitionsCache' => 
  array (
  ),
   'activeDispatchingEvents' => 
  array (
  ),
));
