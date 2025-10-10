<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251010AddDatahubControlPermission extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom admin permission: datahub_control';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO users_permission_definitions (`key`)
            SELECT 'datahub_control'
            WHERE NOT EXISTS (
              SELECT 1 FROM users_permission_definitions WHERE `key` = 'datahub_control'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'datahub_control'");
    }
}
