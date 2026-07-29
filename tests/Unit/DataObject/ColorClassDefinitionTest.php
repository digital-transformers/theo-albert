<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class ColorClassDefinitionTest extends Unit
{
    public function testColorListsFramesThatUseItAsAComposedColor(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_color.php';
        $root = $definition->getLayoutDefinitions();
        $tabPanel = $root->getChildren()[0] ?? null;

        self::assertInstanceOf(Layout\Tabpanel::class, $tabPanel);
        self::assertSame(['Base data', 'Used in Frames'], array_map(
            static fn (Layout|Data $node): ?string => $node->getName(),
            $tabPanel->getChildren()
        ));

        $field = $this->findField($root, 'usedInFrames');

        self::assertInstanceOf(Data\ReverseObjectRelation::class, $field);
        self::assertSame('frame', $field->getOwnerClassName());
        self::assertSame('composedColors', $field->getOwnerFieldName());
        self::assertTrue($field->getLazyLoading());
        self::assertSame('code,name', $field->getVisibleFields());
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
