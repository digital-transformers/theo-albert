<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class CommercialPricingClassDefinitionTest extends Unit
{
    /** @dataProvider basePriceDefinitions */
    public function testBasePriceIsAnUnsignedInteger(string $definitionFile): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/' . $definitionFile;
        $basePrice = $this->findField($definition->getLayoutDefinitions(), 'basePrice');

        self::assertInstanceOf(Data\Numeric::class, $basePrice);
        self::assertTrue($basePrice->getInteger());
        self::assertTrue($basePrice->getUnsigned());
        self::assertSame(0.0, (float) $basePrice->getMinValue());
    }

    /** @return iterable<string, array{string}> */
    public static function basePriceDefinitions(): iterable
    {
        yield 'Family' => ['definition_family.php'];
        yield 'Frame' => ['definition_frame.php'];
    }

    public function testSapPricelistHasCommercialToggle(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_SAPPricelist.php';
        $field = $this->findField($definition->getLayoutDefinitions(), 'commercialPricelist');

        self::assertInstanceOf(Data\Checkbox::class, $field);
        self::assertSame(0, $field->getDefaultValue());
    }

    public function testPricingItemsLinkToPricelistAndUseIsoCurrencies(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/fieldcollections/productPricing.php';
        $pricelist = $definition->getFieldDefinition('pricelist');
        $currency = $definition->getFieldDefinition('currency');

        self::assertInstanceOf(Data\ManyToOneRelation::class, $pricelist);
        self::assertSame([['classes' => 'SAPPricelist']], $pricelist->getClasses());
        self::assertInstanceOf(Data\Select::class, $currency);
        self::assertSame(['EUR', 'GBP', 'USD', 'JPY', 'CHF'], array_column($currency->getOptions(), 'value'));
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
