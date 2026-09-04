<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611084500OptimizeQualityRemarksPortlet extends AbstractMigration
{
    private const TABLES = [
        'object_query_family',
        'object_query_baseProduct',
        'object_query_finishedProduct',
    ];

    private const INDEX_NAME = 'idx_quality_control_remarks_present';

    public function getDescription(): string
    {
        return 'Index quality-control remark presence for the dashboard portlet';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        foreach (self::TABLES as $tableName) {
            if (!$this->tableExists($tableName) || $this->indexExists($tableName, self::INDEX_NAME)) {
                continue;
            }

            $this->addSql(sprintf(
                'CREATE INDEX `%s` ON `%s` (`qualityControlRemarks`(1))',
                self::INDEX_NAME,
                $tableName
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            if (!$this->tableExists($tableName) || !$this->indexExists($tableName, self::INDEX_NAME)) {
                continue;
            }

            $this->addSql(sprintf(
                'DROP INDEX `%s` ON `%s`',
                self::INDEX_NAME,
                $tableName
            ));
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$tableName, $indexName]
        ) > 0;
    }
}
