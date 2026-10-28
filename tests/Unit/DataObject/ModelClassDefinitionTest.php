<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class ModelClassDefinitionTest extends Unit
{
    public function testModelHasLaunchFieldsButNoLensType(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_model.php';

        $launchPeriod = $definition->getFieldDefinition('launchPeriod');
        self::assertInstanceOf(Select::class, $launchPeriod);
        self::assertSame(['jan', 'apr', 'sum', 'silmo'], array_column($launchPeriod->getOptions(), 'value'));
        self::assertSame('configure', $launchPeriod->getOptionsProviderType());
        self::assertSame('', $launchPeriod->getOptionsProviderClass());

        self::assertInstanceOf(Numeric::class, $definition->getFieldDefinition('launchYear'));
        self::assertNull($definition->getFieldDefinition('lensType'));
    }

    public function testModelPanelsRemainSequentialLists(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_model.php';

        foreach (['Base data', 'Details'] as $name) {
            $layout = $this->findLayout($definition->getLayoutDefinitions(), $name);
            self::assertInstanceOf(Layout::class, $layout);
            self::assertTrue(array_is_list($layout->getChildren()));
        }
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
