<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as ObjectWorkspace;
use Pimcore\Model\User\Workspace\Document as DocumentWorkspace;

final class Version20260818120000ConsolidateSupplierRole extends AbstractMigration
{
    private const ROLE_NAME = 'Supplier';
    private const PERMISSION = 'linked_family_supplier';
    private const LEGACY_ROLE_NAMES = [
        'supplier',
        'supplier1',
        'supplierrole1',
    ];

    public function getDescription(): string
    {
        return 'Consolidate supplier roles and restrict suppliers to SAPSupplier-related objects and media.';
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

        $roles = (new Role\Listing())->load();
        $canonical = $this->canonicalRole($roles);
        $canonical->setName(self::ROLE_NAME);
        $canonical->setPermissions(['objects', 'assets', 'documents', self::PERMISSION]);
        $canonical->setClasses([]);
        $canonical->setDocTypes([]);
        $canonical->setPerspectives([]);
        $canonical->setWorkspacesObject([$this->objectWorkspace()]);
        $canonical->setWorkspacesAsset([$this->assetWorkspace()]);
        $canonical->setWorkspacesDocument([$this->documentWorkspace()]);
        $canonical->save();

        foreach ($roles as $role) {
            if (!$role instanceof Role || $role->getId() === $canonical->getId() || !$this->isLegacySupplierRole($role)) {
                continue;
            }

            $this->replaceRoleForUsers((int) $role->getId(), (int) $canonical->getId());
            $this->moveChildRoles((int) $role->getId(), (int) $canonical->getId());
            $role->delete();
        }
    }

    public function down(Schema $schema): void
    {
        // The legacy roles cannot be reconstructed reliably after their users are merged.
    }

    /** @param list<Role> $roles */
    private function canonicalRole(array $roles): Role
    {
        foreach ($roles as $role) {
            if ($role instanceof Role && $role->getName() === self::ROLE_NAME) {
                return $role;
            }
        }
        foreach ($roles as $role) {
            if ($role instanceof Role && strtolower(trim((string) $role->getName())) === 'supplier') {
                return $role;
            }
        }

        return (new Role())
            ->setName(self::ROLE_NAME)
            ->setParentId(0);
    }

    private function isLegacySupplierRole(Role $role): bool
    {
        $name = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $role->getName()) ?? '');

        return in_array($name, self::LEGACY_ROLE_NAMES, true);
    }

    private function replaceRoleForUsers(int $legacyRoleId, int $canonicalRoleId): void
    {
        foreach ((new User\Listing())->load() as $user) {
            if (!$user instanceof User || !in_array($legacyRoleId, $user->getRoles(), true)) {
                continue;
            }

            $roles = array_map(
                static fn (int $roleId): int => $roleId === $legacyRoleId ? $canonicalRoleId : $roleId,
                $user->getRoles()
            );
            $user->setRoles(array_values(array_unique($roles)));
            $user->save();
        }
    }

    private function moveChildRoles(int $legacyRoleId, int $canonicalRoleId): void
    {
        $children = new Role\Listing();
        $children->setCondition('parentId = ?', [$legacyRoleId]);
        foreach ($children->load() as $child) {
            if ($child instanceof Role) {
                $child->setParentId($canonicalRoleId);
                $child->save();
            }
        }
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
            'save' => true,
            'publish' => true,
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
            'create' => true,
            'publish' => false,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => false,
            'properties' => false,
        ]);
    }

    private function documentWorkspace(): DocumentWorkspace
    {
        $root = Document::getById(1);

        return (new DocumentWorkspace())->setValues([
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
            'versions' => false,
            'properties' => false,
        ]);
    }
}
