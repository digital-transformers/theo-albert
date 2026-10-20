<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use App\Model\DataObject\ClassDefinition\Data\TarifCodeOptionsProvider;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;

final class DsTarifClassDefinitionTest extends Unit
{
    /** @dataProvider productDefinitions */
    public function testDsTarifIsASingleSelect(string $definitionFile, string $fieldName): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/' . $definitionFile;
        $field = $definition->getFieldDefinition($fieldName);

        self::assertInstanceOf(Select::class, $field);
        self::assertSame(TarifCodeOptionsProvider::class, $field->getOptionsProviderClass());
    }

    /** @return iterable<string, array{string, string}> */
    public static function productDefinitions(): iterable
    {
        yield 'Frame' => ['definition_frame.php', 'dsTarif'];
        yield 'POS material product' => ['definition_posMaterialProduct.php', 'tarifCode'];
        yield 'Service parts product' => ['definition_servicePartsProduct.php', 'dsTarif'];
    }
}
