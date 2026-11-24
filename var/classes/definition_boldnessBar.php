<?php

/**
 * Inheritance: no
 * Variants: no
 *
 */

return \Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'dao' => NULL,
   'id' => 'boldnessBar',
   'name' => 'boldnessBar',
   'title' => 'Boldness Bar',
   'description' => '',
   'creationDate' => NULL,
   'modificationDate' => 1783095379,
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
             'mandatory' => true,
             'visibleGridView' => true,
          )),
          1 =>
          \Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
             'name' => 'name',
             'title' => 'Name',
             'mandatory' => true,
             'visibleGridView' => true,
          )),
          2 =>
          \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation::__set_state(array(
             'name' => 'linkedFamilies',
             'title' => 'Linked Families',
             'visibleFields' => 'code,name',
             'ownerClassName' => 'family',
             'ownerClassId' => 'family',
             'ownerFieldName' => 'boldnessBarRelation',
          )),
          3 =>
          \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation::__set_state(array(
             'name' => 'linkedFrames',
             'title' => 'Linked Frames',
             'visibleFields' => 'code,name',
             'ownerClassName' => 'frame',
             'ownerClassId' => 'finishedProduct',
             'ownerFieldName' => 'boldnessBarRelation',
          )),
          4 =>
          \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation::__set_state(array(
             'name' => 'linkedModels',
             'title' => 'Linked Models',
             'visibleFields' => 'code,name',
             'ownerClassName' => 'model',
             'ownerClassId' => 'baseProduct',
             'ownerFieldName' => 'boldnessBarRelation',
          )),
          5 =>
          \Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation::__set_state(array(
             'name' => 'linkedPOSMaterials',
             'title' => 'Linked POS Materials',
             'visibleFields' => 'code,name',
             'ownerClassName' => 'posMaterialProduct',
             'ownerClassId' => 'posMaterialProduct',
             'ownerFieldName' => 'boldnessBarRelation',
          )),
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
   'icon' => '',
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
