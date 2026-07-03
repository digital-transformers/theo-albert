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

final class Version20260703163000ExpandMarketingPermissions extends AbstractMigration
{
    private const PRODUCT_ROOT = '/Product Data/Families';

    public function getDescription(): string
    {
        return 'Allow Marketing to manage assets and use automatic image linking.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $role = Role::getByName('Marketing');
        if (!$role instanceof Role) {
            throw new \RuntimeException('Marketing role was not found.');
        }

        $productRoot = DataObject::getByPath(self::PRODUCT_ROOT);
        $assetRoot = Asset::getById(1);
        if (!$productRoot instanceof DataObject || !$assetRoot instanceof Asset) {
            throw new \RuntimeException('Marketing workspace roots were not found.');
        }

        $role->setPermissions(['objects', 'assets', 'marketing_only', 'automatic_image_linking']);
        $role->setClasses($this->resolveClassIds(['family', 'model', 'frame']));
        $role->setWorkspacesObject([$this->objectWorkspace($productRoot)]);
        $role->setWorkspacesAsset([$this->assetWorkspace($assetRoot)]);
        $role->save();
    }

    public function down(Schema $schema): void
    {
    }

    private function objectWorkspace(DataObject $root): ObjectWorkspace
    {
        return (new ObjectWorkspace())->setValues([
            'cId' => (int) $root->getId(),
            'cPath' => $root->getRealFullPath(),
            'list' => true,
            'view' => true,
            'create' => false,
            'save' => true,
            'publish' => true,
            'unpublish' => true,
            'delete' => false,
            'rename' => false,
            'settings' => false,
            'versions' => true,
            'properties' => false,
        ]);
    }

    private function assetWorkspace(Asset $root): AssetWorkspace
    {
        return (new AssetWorkspace())->setValues([
            'cId' => (int) $root->getId(),
            'cPath' => '/',
            'list' => true,
            'view' => true,
            'create' => true,
            'publish' => true,
            'delete' => true,
            'rename' => true,
            'settings' => true,
            'versions' => true,
            'properties' => true,
        ]);
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
