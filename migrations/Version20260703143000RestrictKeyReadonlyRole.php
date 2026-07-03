<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as ObjectWorkspace;

final class Version20260703143000RestrictKeyReadonlyRole extends AbstractMigration
{
    private const PERMISSION = 'key_readonly';

    public function getDescription(): string
    {
        return 'Allow Key-Readonly to view every data object and asset without modifying them.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            "INSERT INTO users_permission_definitions (`key`) SELECT '%s' WHERE NOT EXISTS (SELECT 1 FROM users_permission_definitions WHERE `key` = '%s')",
            self::PERMISSION,
            self::PERMISSION
        ));

        $role = Role::getByName('Key-Readonly');
        if (!$role instanceof Role) {
            throw new \RuntimeException('Key-Readonly role was not found.');
        }

        $role->setPermissions(['objects', 'assets', self::PERMISSION]);
        $role->setClasses($this->allClassIds());
        $role->setWorkspacesObject([$this->objectWorkspace()]);
        $role->setWorkspacesAsset([$this->assetWorkspace()]);
        $role->setWorkspacesDocument([]);
        $role->setDocTypes([]);
        $role->setPerspectives([]);
        $role->save();
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf("DELETE FROM users_permission_definitions WHERE `key` = '%s'", self::PERMISSION));
    }

    /** @return list<string> */
    private function allClassIds(): array
    {
        return array_values(array_map(
            static fn (ClassDefinition $definition): string => (string) $definition->getId(),
            (new ClassDefinition\Listing())->load()
        ));
    }

    private function objectWorkspace(): ObjectWorkspace
    {
        $root = DataObject::getById(1);

        return (new ObjectWorkspace())->setValues([
            'cId' => $root?->getId() ?? 1,
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => false,
            'save' => false,
            'publish' => false,
            'unpublish' => false,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => true,
            'properties' => false,
        ]);
    }

    private function assetWorkspace(): AssetWorkspace
    {
        $root = Asset::getById(1);

        return (new AssetWorkspace())->setValues([
            'cId' => $root?->getId() ?? 1,
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => false,
            'publish' => false,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => true,
            'properties' => false,
        ]);
    }
}
