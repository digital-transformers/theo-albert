<?php
declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250915090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom permission: see_own_objects_only';
    }

    public function up(Schema $schema): void
    {
        // Pimcore uses MySQL/MariaDB; keep a guard for clarity.
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        // Create permission if it does not exist
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM users_permission_definitions WHERE `key` = ?',
            ['see_own_objects_only']
        );

        if ($exists === 0) {
            $this->addSql("INSERT INTO users_permission_definitions (`key`) VALUES ('see_own_objects_only')");
        }
    }

    public function down(Schema $schema): void
    {
        // Remove the permission (safe even if already deleted)
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'see_own_objects_only'");
    }
}
