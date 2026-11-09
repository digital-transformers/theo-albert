<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;

final class SapSyncClassDefinitionTest extends Unit
{
    public function testSapComponentsSupportBrutAcetateAndConsumedQuantity(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_SAPComponent.php';

        $itemType = $definition->getFieldDefinition('itemType');
        self::assertInstanceOf(Multiselect::class, $itemType);
        self::assertContains('brut', array_column($itemType->getOptions(), 'value'));
        self::assertContains('acetate', array_column($itemType->getOptions(), 'value'));
        self::assertContains('oper', array_column($itemType->getOptions(), 'value'));

        $quantity = $definition->getFieldDefinition('quantity');
        self::assertInstanceOf(Numeric::class, $quantity);
        self::assertSame(1.0, (float) $quantity->getDefaultValue());
        self::assertFalse($quantity->getInteger());
        self::assertTrue($quantity->getUnsigned());

        self::assertNull($definition->getFieldDefinition('components'));

        self::assertTrue($definition->getFieldDefinition('step')->getInvisible());
        self::assertTrue($definition->getFieldDefinition('supplier')->getInvisible());

        $suppliers = $definition->getFieldDefinition('suppliers');
        self::assertInstanceOf(AdvancedManyToManyObjectRelation::class, $suppliers);
        self::assertSame('supplier', $suppliers->getAllowedClassId());
        self::assertSame(1, $suppliers->getMaxItems());
        self::assertSame(['supplierType', 'leadTime', 'workItemGroup'], $suppliers->getColumnKeys());
    }

    public function testModelComponentsMirrorTheFrameComponentRelation(): void
    {
        $model = require dirname(__DIR__, 3) . '/var/classes/definition_model.php';
        $frame = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';

        $modelComponents = $model->getFieldDefinition('components');
        $frameComponents = $frame->getFieldDefinition('components');

        self::assertInstanceOf(AdvancedManyToManyObjectRelation::class, $modelComponents);
        self::assertInstanceOf(AdvancedManyToManyObjectRelation::class, $frameComponents);
        self::assertSame($frameComponents->getAllowedClassId(), $modelComponents->getAllowedClassId());
        self::assertSame($frameComponents->getColumnKeys(), $modelComponents->getColumnKeys());
        self::assertSame($frameComponents->getAllowMultipleAssignments(), $modelComponents->getAllowMultipleAssignments());
    }

    public function testProcessContainsSapRoutingAndLeadTimeDefaults(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_process.php';

        self::assertSame(['BRUT_AF', 'COLOR', 'QUALITY'], array_column($definition->getFieldDefinition('sapStep')->getOptions(), 'value'));
        self::assertInstanceOf(Numeric::class, $definition->getFieldDefinition('order'));
        self::assertInstanceOf(Checkbox::class, $definition->getFieldDefinition('create'));
        self::assertSame(1, $definition->getFieldDefinition('create')->getDefaultValue());
        self::assertInstanceOf(ManyToOneRelation::class, $definition->getFieldDefinition('itemGroup'));
        self::assertInstanceOf(ManyToOneRelation::class, $definition->getFieldDefinition('workItemGroup'));
        foreach (['leadTimeMetalSingleColor', 'leadTimeMetalBiColor', 'leadTimeAcetate', 'leadTimeMetalAcetate'] as $field) {
            self::assertInstanceOf(Numeric::class, $definition->getFieldDefinition($field));
        }
    }

    public function testFrameContainsSapAndCommerceFieldsAndMetadata(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';

        foreach (['sapItemCode', 'sapItemName', 'sapFamily'] as $field) {
            self::assertInstanceOf(Input::class, $definition->getFieldDefinition($field));
        }
        self::assertInstanceOf(ManyToOneRelation::class, $definition->getFieldDefinition('combiColor'));
        self::assertSame('color', $definition->getFieldDefinition('combiColor')->getClasses()[0]['classes']);
        self::assertInstanceOf(Checkbox::class, $definition->getFieldDefinition('sapActive'));
        self::assertInstanceOf(Checkbox::class, $definition->getFieldDefinition('isNew'));
        self::assertSame(
            ['name', 'relevant', 'sapColorCode', 'orderNr', 'comment', 'qty'],
            $definition->getFieldDefinition('composedColors')->getColumnKeys(),
        );
    }

    public function testSupplierOverridesAndProcessDetailsAreAvailable(): void
    {
        foreach (['family', 'model', 'frame'] as $class) {
            $definition = require dirname(__DIR__, 3) . '/var/classes/definition_' . $class . '.php';
            self::assertSame(
                ['supplierType', 'leadTime', 'workItemGroup'],
                $definition->getFieldDefinition('suppliers')->getColumnKeys(),
            );
        }

        $supplier = require dirname(__DIR__, 3) . '/var/classes/definition_supplier.php';
        $processDetails = $supplier->getFieldDefinition('processDetails');
        self::assertInstanceOf(Fieldcollections::class, $processDetails);
        self::assertSame(['supplierProcessDetails'], $processDetails->getAllowedTypes());

        $collection = require dirname(__DIR__, 3) . '/var/classes/fieldcollections/supplierProcessDetails.php';
        self::assertSame(
            ['process', 'leadTime', 'itemGroup', 'workItemGroup', 'priceList'],
            array_keys($collection->getFieldDefinitions()),
        );
    }

    public function testPricingCalculatedValueUsesCalculatorClass(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/fieldcollections/productPricing.php';
        $field = $definition->getFieldDefinition('priceAmount');

        self::assertSame('class', $field->getCalculatorType());
        self::assertSame('App\\DataObject\\Calculator\\ProductPricingAmountCalculator', $field->getCalculatorClass());
    }
}
