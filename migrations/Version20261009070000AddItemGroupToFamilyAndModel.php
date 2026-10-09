<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class Version20261009070000AddItemGroupToFamilyAndModel extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Item Group relations to Family and Model objects.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        foreach (['family', 'model'] as $className) {
            $this->addItemGroupField($className);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing Item Group fields would discard saved relations.');
    }

    private function addItemGroupField(string $className): void
    {
        $definition = ClassDefinition::getByName($className);
        if (!$definition instanceof ClassDefinition) {
            throw new \RuntimeException(sprintf('Class %s was not found.', $className));
        }

        $frameDefinition = ClassDefinition::getByName('frame');
        $frameItemGroup = $frameDefinition?->getFieldDefinitions()['itemGroup'] ?? null;
        if (!$frameItemGroup instanceof ManyToManyObjectRelation) {
            throw new \RuntimeException('Frame Item Group field was not found.');
        }

        $field = clone $frameItemGroup;

        $panel = $this->findPanel($definition->getLayoutDefinitions(), 'Base data');
        if (!$panel instanceof Layout) {
            throw new \RuntimeException(sprintf('%s Base data panel was not found.', $className));
        }

        $children = $panel->getChildren();
        $insertAt = count($children);
        foreach ($children as $index => $child) {
            if (method_exists($child, 'getName') && $child->getName() === 'itemGroup') {
                $children[$index] = $field;
                $panel->setChildren($children);
                $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
                $definition->save();

                return;
            }
            if (method_exists($child, 'getName') && $child->getName() === 'name') {
                $insertAt = $index + 1;
            }
        }

        array_splice($children, $insertAt, 0, [$field]);
        $panel->setChildren($children);
        $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
        $definition->save();
    }

    private function findPanel(mixed $layout, string $name): ?Layout
    {
        if (!$layout instanceof Layout) {
            return null;
        }
        if ((string) $layout->getName() === $name) {
            return $layout;
        }
        foreach ($layout->getChildren() as $child) {
            $panel = $this->findPanel($child, $name);
            if ($panel instanceof Layout) {
                return $panel;
            }
        }

        return null;
    }
}
