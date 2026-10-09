<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;

final class ItemGroupClassDefinitionTest extends Unit
{
    /** @dataProvider productDefinitions */
    public function testItemGroupRelationMatchesFrameConfiguration(string $definitionFile): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/' . $definitionFile;
        $field = $definition->getFieldDefinition('itemGroup');

        self::assertInstanceOf(ManyToManyObjectRelation::class, $field);
        self::assertSame([['classes' => 'SAPItemGroup']], $field->getClasses());
        self::assertSame('groupNum,groupName', $field->getVisibleFields());
        self::assertFalse($field->isAllowToCreateNewObject());
        self::assertTrue($field->isAllowedToClearRelation());
    }

    /** @return iterable<string, array{string}> */
    public static function productDefinitions(): iterable
    {
        yield 'Family' => ['definition_family.php'];
        yield 'Model' => ['definition_model.php'];
        yield 'Frame' => ['definition_frame.php'];
    }
}
