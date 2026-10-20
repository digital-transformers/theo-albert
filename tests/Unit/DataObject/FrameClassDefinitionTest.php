<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

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

    public function testBaseDataChildrenRemainASequentialList(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';
        $baseData = $this->findLayout($definition->getLayoutDefinitions(), 'Base data');

        self::assertInstanceOf(Layout::class, $baseData);
        self::assertTrue(array_is_list($baseData->getChildren()));
    }

    private function findLayout(?Layout $layout, string $name): ?Layout
    {
        if (!$layout instanceof Layout) {
            return null;
        }
        if ($layout->getName() === $name) {
            return $layout;
        }
        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Layout && ($match = $this->findLayout($child, $name))) {
                return $match;
            }
        }

        return null;
    }
}
