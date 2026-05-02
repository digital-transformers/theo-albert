<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428130500AddQualityControlPermission extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add custom permission: quality_control';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM users_permission_definitions WHERE `key` = ?',
            ['quality_control']
        );

        if ($exists === 0) {
            $this->addSql("INSERT INTO users_permission_definitions (`key`) VALUES ('quality_control')");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users_permission_definitions WHERE `key` = 'quality_control'");
    }
}
