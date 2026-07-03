<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\User as UserField;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as ObjectWorkspace;

final class Version20260703173000LinkedFamilyRoles extends AbstractMigration
{
    private const DESIGNER_PERMISSION = 'linked_family_designer';
    private const SUPPLIER_PERMISSION = 'linked_family_supplier';

    public function getDescription(): string
    {
        return 'Link Designer and Supplier objects to users and restrict their roles to assigned family trees.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        foreach ([self::DESIGNER_PERMISSION, self::SUPPLIER_PERMISSION] as $permission) {
            $this->addSql(sprintf(
                "INSERT INTO users_permission_definitions (`key`) SELECT '%s' WHERE NOT EXISTS (SELECT 1 FROM users_permission_definitions WHERE `key` = '%s')",
                $permission,
                $permission
            ));
        }

        $this->addUserField('designer');
        $this->addUserField('supplier');
        $this->configureDesignerRole('Designer-Internal');
        $this->configureDesignerRole('Designer-External');
        $this->configureSupplierRole();
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf(
            "DELETE FROM users_permission_definitions WHERE `key` IN ('%s', '%s')",
            self::DESIGNER_PERMISSION,
            self::SUPPLIER_PERMISSION
        ));
    }

    private function addUserField(string $className): void
    {
        $definition = ClassDefinition::getByName($className);
        if (!$definition instanceof ClassDefinition) {
            throw new \RuntimeException(sprintf('Class "%s" was not found.', $className));
        }
        if (($definition->getFieldDefinitions()['pimcoreUser'] ?? null) instanceof UserField) {
            // A previous attempt may have persisted the field before generated-file writing failed.
            $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
            $definition->save();

            return;
        }

        $field = (new UserField())
            ->setName('pimcoreUser')
            ->setTitle('Pimcore user')
            ->setMandatory(false)
            ->setNoteditable(false);
        $field->setUnique(true);

        $panel = $this->firstPanel($definition->getLayoutDefinitions());
        if (!$panel instanceof Layout) {
            throw new \RuntimeException(sprintf('Class "%s" has no panel for the user field.', $className));
        }
        $panel->addChild($field);
        $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
        $definition->save();
    }

    private function firstPanel(mixed $layout): ?Layout
    {
        if (!$layout instanceof Layout) {
            return null;
        }
        if ((string) $layout->getName() === 'Base data') {
            return $layout;
        }
        foreach ($layout->getChildren() as $child) {
            $panel = $this->firstPanel($child);
            if ($panel instanceof Layout) {
                return $panel;
            }
        }

        return $layout;
    }

    private function configureDesignerRole(string $name): void
    {
        $role = $this->role($name);
        $role->setPermissions([
            'objects',
            'assets',
            'quality_control',
            'family_phase_update',
            'family_launch_update',
            'model_frame_generate',
            self::DESIGNER_PERMISSION,
        ]);
        $role->setClasses($this->resolveClassIds(['family', 'model', 'frame', 'color']));
        $role->setWorkspacesObject([$this->objectWorkspace(true, true, true)]);
        $role->setWorkspacesAsset([$this->assetWorkspace(true, true)]);
        $role->save();
    }

    private function configureSupplierRole(): void
    {
        $role = $this->role('Supplier');
        $role->setPermissions(['objects', 'assets', self::SUPPLIER_PERMISSION]);
        $role->setClasses($this->resolveClassIds(['family', 'model', 'frame']));
        $role->setWorkspacesObject([$this->objectWorkspace(false, false, false)]);
        $role->setWorkspacesAsset([$this->assetWorkspace(false, false)]);
        $role->save();
    }

    private function role(string $name): Role
    {
        $role = Role::getByName($name);
        if (!$role instanceof Role) {
            throw new \RuntimeException(sprintf('Role "%s" was not found.', $name));
        }

        return $role;
    }

    private function objectWorkspace(bool $create, bool $save, bool $publish): ObjectWorkspace
    {
        $root = DataObject::getById(1);

        return (new ObjectWorkspace())->setValues([
            'cId' => $root?->getId() ?? 1,
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => $create,
            'save' => $save,
            'publish' => $publish,
            'unpublish' => $publish,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => true,
            'properties' => false,
        ]);
    }

    private function assetWorkspace(bool $create, bool $publish): AssetWorkspace
    {
        $root = Asset::getById(1);

        return (new AssetWorkspace())->setValues([
            'cId' => $root?->getId() ?? 1,
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => $create,
            'publish' => $publish,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => true,
            'properties' => false,
        ]);
    }

    /** @param list<string> $classNames
     *  @return list<string>
     */
    private function resolveClassIds(array $classNames): array
    {
        $ids = [];
        foreach ($classNames as $className) {
            $definition = ClassDefinition::getByName($className);
            if ($definition instanceof ClassDefinition) {
                $ids[] = (string) $definition->getId();
            }
        }

        return $ids;
    }
}
