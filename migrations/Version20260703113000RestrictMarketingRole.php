<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\DataObject as ObjectWorkspace;

final class Version20260703113000RestrictMarketingRole extends AbstractMigration
{
    private const PRODUCT_ROOT = '/Product Data/Families';

    public function getDescription(): string
    {
        return 'Restrict Marketing object access to Family, Model, and Frame under the product hierarchy.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->configureMarketingRole(self::PRODUCT_ROOT);
    }

    public function down(Schema $schema): void
    {
        $this->configureMarketingRole('/');
    }

    private function configureMarketingRole(string $workspacePath): void
    {
        $role = Role::getByName('Marketing');
        if (!$role instanceof Role) {
            throw new \RuntimeException('Marketing role was not found.');
        }

        $root = DataObject::getByPath($workspacePath);
        if (!$root instanceof DataObject) {
            throw new \RuntimeException(sprintf('Marketing workspace path "%s" was not found.', $workspacePath));
        }

        $role->setPermissions(['objects', 'assets', 'marketing_only']);
        $role->setClasses($this->resolveClassIds(['family', 'model', 'frame']));
        $role->setWorkspacesObject([($this->workspace($root))]);
        $role->save();
    }

    private function workspace(DataObject $root): ObjectWorkspace
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
