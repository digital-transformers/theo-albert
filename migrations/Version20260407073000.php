<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407073000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Pimcore 12.3 asset grid configuration sharing column';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('gridconfigs')) {
            return;
        }

        $table = $schema->getTable('gridconfigs');

        if (!$table->hasColumn('shareBetweenFolders')) {
            $table->addColumn('shareBetweenFolders', 'boolean', ['notnull' => false]);
        }

        if (!$table->hasIndex('shareBetweenFolders')) {
            $table->addIndex(['shareBetweenFolders'], 'shareBetweenFolders');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'shareBetweenFolders is a Pimcore core schema column from Pimcore 12.3; do not drop it during rollback.'
        );
    }
}
