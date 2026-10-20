<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;

final class FrameClassDefinitionTest extends Unit
{
    public function testFrameHasEANCodeInput(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';
        $field = $definition->getFieldDefinition('EANCode');

        self::assertInstanceOf(Input::class, $field);
        self::assertSame('EAN Code', $field->getTitle());
        self::assertSame(190, $field->getColumnLength());
    }

    public function testFrameSuppliersIncludeSupplierTypeMetadata(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';
        $field = $definition->getFieldDefinition('suppliers');

        self::assertInstanceOf(AdvancedManyToManyObjectRelation::class, $field);
        self::assertSame('supplier', $field->getAllowedClassId());
        self::assertSame(['supplierType'], $field->getColumnKeys());
        self::assertSame('multiselect', $field->getColumns()[0]['type']);
        self::assertTrue($field->getAllowMultipleAssignments());
    }
}
