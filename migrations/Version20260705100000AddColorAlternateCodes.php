<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Textarea;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class Version20260705100000AddColorAlternateCodes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add newline-separated alternate codes to Color objects.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $definition = ClassDefinition::getByName('color');
        if (!$definition instanceof ClassDefinition) {
            throw new \RuntimeException('Color class was not found.');
        }

        if (($definition->getFieldDefinitions()['alternateCodes'] ?? null) instanceof Textarea) {
            $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
            $definition->save();

            return;
        }

        $field = (new Textarea())
            ->setName('alternateCodes')
            ->setTitle('Alternate codes')
            ->setTooltip('Enter one alternative color code per line.')
            ->setMandatory(false)
            ->setNoteditable(false)
            ->setHeight(120);

        $panel = $this->findPanel($definition->getLayoutDefinitions(), 'Base data');
        if (!$panel instanceof Layout) {
            throw new \RuntimeException('Color Base data panel was not found.');
        }
        $panel->addChild($field);
        $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
        $definition->save();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing the field would discard alternate color codes.');
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
