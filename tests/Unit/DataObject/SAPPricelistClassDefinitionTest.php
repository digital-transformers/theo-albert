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
