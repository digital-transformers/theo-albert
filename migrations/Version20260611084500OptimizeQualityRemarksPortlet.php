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
            if (!$schema->hasTable($tableName) || $schema->getTable($tableName)->hasIndex(self::INDEX_NAME)) {
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
            if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasIndex(self::INDEX_NAME)) {
                continue;
            }

            $this->addSql(sprintf(
                'DROP INDEX `%s` ON `%s`',
                self::INDEX_NAME,
                $tableName
            ));
        }
    }
}
