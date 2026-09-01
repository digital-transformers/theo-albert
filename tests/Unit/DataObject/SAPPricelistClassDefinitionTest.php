<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class SAPPricelistClassDefinitionTest extends Unit
{
    public function testCurrencyIsASelectWithSymbolsAndIsoCodes(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_SAPPricelist.php';
        $currency = $this->findField($definition->getLayoutDefinitions(), 'currency');

        self::assertInstanceOf(Data\Select::class, $currency);
        self::assertSame([
            ['key' => '€', 'value' => 'EUR'],
            ['key' => '£', 'value' => 'GBP'],
            ['key' => 'US$', 'value' => 'USD'],
            ['key' => '¥', 'value' => 'JPY'],
            ['key' => 'CHF', 'value' => 'CHF'],
        ], $currency->getOptions());
    }

    public function testCommercialPricelistIsACheckbox(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_SAPPricelist.php';
        $commercial = $this->findField($definition->getLayoutDefinitions(), 'commercialPricelist');

        self::assertInstanceOf(Data\Checkbox::class, $commercial);
        self::assertSame(0, $commercial->getDefaultValue());
    }

    public function testRoundingIsASelectDefaultingToNo(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_SAPPricelist.php';
        $rounding = $this->findField($definition->getLayoutDefinitions(), 'rounding');

        self::assertInstanceOf(Data\Select::class, $rounding);
        self::assertTrue($rounding->getMandatory());
        self::assertSame('no', $rounding->getDefaultValue());
        self::assertSame([
            ['key' => 'No rounding', 'value' => 'no'],
            ['key' => 'Upper integer', 'value' => 'upper_1'],
            ['key' => 'Upper 5', 'value' => 'upper_5'],
        ], $rounding->getOptions());
    }

    private function findField(Layout|Data|null $node, string $name): ?Data
    {
        if ($node instanceof Data && $node->getName() === $name) {
            return $node;
        }

        if (!$node instanceof Layout) {
            return null;
        }

        foreach ($node->getChildren() as $child) {
            $field = $this->findField($child, $name);
            if ($field instanceof Data) {
                return $field;
            }
        }

        return null;
    }
}
