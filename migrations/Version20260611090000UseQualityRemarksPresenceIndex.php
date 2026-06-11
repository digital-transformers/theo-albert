<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611090000UseQualityRemarksPresenceIndex extends AbstractMigration
{
    private const TABLES = [
        'object_query_family',
        'object_query_baseProduct',
        'object_query_finishedProduct',
    ];

    private const OLD_INDEX_NAME = 'idx_quality_control_remarks_present';
    private const PRESENCE_COLUMN = 'qualityControlRemarksPresent';
    private const INDEX_NAME = 'idx_quality_control_remarks_presence';

    public function getDescription(): string
    {
        return 'Use an indexed generated flag for non-empty quality-control remarks';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        foreach (self::TABLES as $tableName) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            if ($table->hasIndex(self::OLD_INDEX_NAME)) {
                $this->addSql(sprintf(
                    'DROP INDEX `%s` ON `%s`',
                    self::OLD_INDEX_NAME,
                    $tableName
                ));
            }

            if (!$table->hasColumn(self::PRESENCE_COLUMN)) {
                $this->addSql(sprintf(
                    'ALTER TABLE `%s` ADD COLUMN `%s` TINYINT(1) GENERATED ALWAYS AS (CHAR_LENGTH(`qualityControlRemarks`) > 0) STORED',
                    $tableName,
                    self::PRESENCE_COLUMN
                ));
            }

            if (!$table->hasIndex(self::INDEX_NAME)) {
                $this->addSql(sprintf(
                    'CREATE INDEX `%s` ON `%s` (`%s`, `oo_id`)',
                    self::INDEX_NAME,
                    $tableName,
                    self::PRESENCE_COLUMN
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            if ($table->hasIndex(self::INDEX_NAME)) {
                $this->addSql(sprintf(
                    'DROP INDEX `%s` ON `%s`',
                    self::INDEX_NAME,
                    $tableName
                ));
            }

            if ($table->hasColumn(self::PRESENCE_COLUMN)) {
                $this->addSql(sprintf(
                    'ALTER TABLE `%s` DROP COLUMN `%s`',
                    $tableName,
                    self::PRESENCE_COLUMN
                ));
            }

            if (!$table->hasIndex(self::OLD_INDEX_NAME)) {
                $this->addSql(sprintf(
                    'CREATE INDEX `%s` ON `%s` (`qualityControlRemarks`(1))',
                    self::OLD_INDEX_NAME,
                    $tableName
                ));
            }
        }
    }
}
