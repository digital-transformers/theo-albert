<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\Asset;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;

final class Version20260519173000CreatePhotographerRole extends AbstractMigration
{
    private const ROLE_NAME = 'Photographer';
    private const UPLOAD_FOLDER_PATH = '/Upload Frame Pictures';

    public function getDescription(): string
    {
        return 'Create Photographer role restricted to the Upload Frame Pictures asset folder.';
    }

    public function up(Schema $schema): void
    {
        $folder = $this->getOrCreateUploadFolder();
        $role = Role::getByName(self::ROLE_NAME) ?? new Role();

        if (!$role->getId()) {
            $role->setName(self::ROLE_NAME);
            $role->setParentId(0);
        }

        $workspace = new AssetWorkspace();
        $workspace->setCid((int) $folder->getId());
        $workspace->setCpath($folder->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setCreate(true);
        $workspace->setPublish(false);
        $workspace->setDelete(false);
        $workspace->setRename(false);
        $workspace->setSettings(false);
        $workspace->setVersions(false);
        $workspace->setProperties(false);

        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset([$workspace]);
        $role->setWorkspacesDocument([]);
        $role->setWorkspacesObject([]);
        $role->setClasses([]);
        $role->setDocTypes([]);
        $role->setPerspectives([]);
        $role->save();
    }

    public function down(Schema $schema): void
    {
        $role = Role::getByName(self::ROLE_NAME);
        if ($role instanceof Role) {
            $role->delete();
        }
    }

    private function getOrCreateUploadFolder(): Asset\Folder
    {
        $folder = Asset::getByPath(self::UPLOAD_FOLDER_PATH);
        if ($folder instanceof Asset\Folder) {
            return $folder;
        }

        $folder = Asset\Service::createFolderByPath(self::UPLOAD_FOLDER_PATH);
        if (!$folder instanceof Asset\Folder) {
            throw new \RuntimeException(sprintf('Unable to create asset folder "%s".', self::UPLOAD_FOLDER_PATH));
        }

        return $folder;
    }
}
