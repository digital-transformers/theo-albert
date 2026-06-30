<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Model\User\Workspace\DataObject as ObjectWorkspace;

final class Version20260630100000EnforceTheoRolePermissions extends AbstractMigration
{
    private const CUSTOM_PERMISSIONS = [
        'family_phase_update',
        'family_launch_update',
        'supplier_projects_only',
        'model_frame_generate',
        'quality_control_only',
        'marketing_only',
        'automatic_image_linking',
    ];

    private const PICTURES_UPLOAD_FOLDER_PATH = '/Upload Frame Pictures';

    private const PRODUCT_CLASSES = [
        'family',
        'model',
        'frame',
        'downloadableAsset',
        'posMaterialProduct',
        'servicePartsProduct',
        'supplier',
        'process',
    ];

    public function getDescription(): string
    {
        return 'Reapply Theo role permissions according to the product/assets access matrix.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        foreach (self::CUSTOM_PERMISSIONS as $permission) {
            $this->addSql(sprintf(
                "INSERT INTO users_permission_definitions (`key`) SELECT '%s' WHERE NOT EXISTS (SELECT 1 FROM users_permission_definitions WHERE `key` = '%s')",
                $permission,
                $permission
            ));
        }

        $this->configureRoles();
        $this->assignUserToRole(['Caroline', 'caroline'], 'Key-Readonly');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'automatic_image_linking'");
    }

    private function configureRoles(): void
    {
        $productClassIds = $this->resolveClassIds(self::PRODUCT_CLASSES);
        $designerClassIds = $this->resolveClassIds(['family', 'model']);
        $supplierClassIds = $this->resolveClassIds(['family', 'model', 'frame']);
        $qcClassIds = $this->resolveClassIds(['family', 'model', 'frame']);
        $marketingClassIds = $this->resolveClassIds(['family', 'model', 'frame']);

        $this->saveRole(
            'Designer-Internal',
            [
                'objects',
                'assets',
                'family_phase_update',
                'family_launch_update',
                'model_frame_generate',
            ],
            [$this->objectRootWorkspace(create: true, save: true, publish: true)],
            [$this->assetRootWorkspace(create: true, publish: true)],
            $designerClassIds
        );

        $this->saveRole(
            'Designer-External',
            [
                'objects',
                'assets',
                'see_own_objects_only',
            ],
            [$this->objectRootWorkspace(create: true, save: true, publish: true)],
            [$this->assetRootWorkspace(create: true, publish: true)],
            $designerClassIds
        );

        $this->saveRole(
            'Supplier',
            [
                'objects',
                'assets',
                'supplier_projects_only',
            ],
            [$this->objectRootWorkspace(create: false, save: true, publish: false)],
            [$this->assetRootWorkspace(create: true, publish: true)],
            $supplierClassIds
        );

        $this->saveRole(
            'Quality-Control-user',
            [
                'objects',
                'assets',
                'quality_control',
                'quality_control_only',
            ],
            [$this->objectRootWorkspace(create: false, save: true, publish: true)],
            [$this->assetRootWorkspace(create: true, publish: true)],
            $qcClassIds
        );

        $this->saveRole(
            'Key-User',
            [
                'objects',
                'assets',
                'documents',
                'fieldcollections',
                'classificationstore',
                'quality_control',
                'family_phase_update',
                'family_launch_update',
                'model_frame_generate',
                'automatic_image_linking',
            ],
            [$this->objectRootWorkspace(create: true, save: true, publish: true, delete: true, rename: true, settings: true, versions: true, properties: true)],
            [$this->assetRootWorkspace(create: true, publish: true, delete: true, rename: true, settings: true, versions: true, properties: true)],
            $productClassIds
        );

        $this->saveRole(
            'Key-Readonly',
            [
                'objects',
                'assets',
                'documents',
            ],
            [$this->objectRootWorkspace(create: false, save: false, publish: false)],
            [$this->assetRootWorkspace(create: false, publish: false)],
            $productClassIds
        );

        $this->saveRole(
            'Pictures',
            [
                'assets',
                'automatic_image_linking',
            ],
            [],
            [$this->assetWorkspace(self::PICTURES_UPLOAD_FOLDER_PATH, create: true, publish: false)],
            []
        );

        $this->saveRole(
            'Marketing',
            [
                'objects',
                'assets',
                'marketing_only',
            ],
            [$this->objectRootWorkspace(create: false, save: true, publish: true)],
            [$this->assetRootWorkspace(create: true, publish: true)],
            $marketingClassIds
        );
    }

    /**
     * @param list<string> $permissions
     * @param list<ObjectWorkspace> $objectWorkspaces
     * @param list<AssetWorkspace> $assetWorkspaces
     * @param list<string> $classes
     */
    private function saveRole(
        string $name,
        array $permissions,
        array $objectWorkspaces,
        array $assetWorkspaces,
        array $classes
    ): void {
        $role = Role::getByName($name) ?? new Role();
        if (!$role->getId()) {
            $role->setName($name);
            $role->setParentId(0);
        }

        $role->setPermissions($permissions);
        $role->setWorkspacesObject($objectWorkspaces);
        $role->setWorkspacesAsset($assetWorkspaces);
        $role->setWorkspacesDocument([]);
        $role->setClasses($classes);
        $role->setDocTypes([]);
        $role->setPerspectives([]);
        $role->save();
    }

    private function objectRootWorkspace(
        bool $create,
        bool $save,
        bool $publish,
        bool $delete = false,
        bool $rename = false,
        bool $settings = false,
        bool $versions = true,
        bool $properties = false
    ): ObjectWorkspace {
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
            'delete' => $delete,
            'rename' => $rename,
            'settings' => $settings,
            'versions' => $versions,
            'properties' => $properties,
        ]);
    }

    private function assetRootWorkspace(
        bool $create,
        bool $publish,
        bool $delete = false,
        bool $rename = false,
        bool $settings = false,
        bool $versions = true,
        bool $properties = false
    ): AssetWorkspace {
        $root = Asset::getById(1);

        return (new AssetWorkspace())->setValues([
            'cId' => $root?->getId() ?? 1,
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => $create,
            'publish' => $publish,
            'delete' => $delete,
            'rename' => $rename,
            'settings' => $settings,
            'versions' => $versions,
            'properties' => $properties,
        ]);
    }

    private function assetWorkspace(
        string $path,
        bool $create,
        bool $publish,
        bool $delete = false,
        bool $rename = false,
        bool $settings = false,
        bool $versions = true,
        bool $properties = false
    ): AssetWorkspace {
        $folder = $this->getOrCreateAssetFolder($path);

        return (new AssetWorkspace())->setValues([
            'cId' => (int) $folder->getId(),
            'cPath' => $folder->getRealFullPath(),
            'list' => true,
            'view' => true,
            'create' => $create,
            'publish' => $publish,
            'delete' => $delete,
            'rename' => $rename,
            'settings' => $settings,
            'versions' => $versions,
            'properties' => $properties,
        ]);
    }

    private function getOrCreateAssetFolder(string $path): Asset\Folder
    {
        $folder = Asset::getByPath($path);
        if ($folder instanceof Asset\Folder) {
            return $folder;
        }

        $folder = Asset\Service::createFolderByPath($path);
        if (!$folder instanceof Asset\Folder) {
            throw new \RuntimeException(sprintf('Unable to create asset folder "%s".', $path));
        }

        return $folder;
    }

    /**
     * @param list<string> $usernames
     */
    private function assignUserToRole(array $usernames, string $roleName): void
    {
        $role = Role::getByName($roleName);
        if (!$role instanceof Role || !$role->getId()) {
            return;
        }

        foreach ($usernames as $username) {
            $user = User::getByName($username);
            if (!$user instanceof User) {
                continue;
            }

            $roles = array_map('intval', $user->getRoles());
            $roleId = (int) $role->getId();
            if (!in_array($roleId, $roles, true)) {
                $roles[] = $roleId;
                $user->setRoles($roles);
                $user->save();
            }
        }
    }

    /**
     * @param list<string> $classNames
     *
     * @return list<string>
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
