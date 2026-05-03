<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503090500SyncQualityControlObjectTables extends AbstractMigration
{
    /**
     * @var array<string, array{store: array<string, string>, query: array<string, string>}>
     */
    private const TABLES = [
        'family' => [
            'store' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
            'query' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlDocuments' => 'TEXT DEFAULT NULL',
                'qualityControlImages' => 'TEXT DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
        ],
        'baseProduct' => [
            'store' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
            'query' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlDocuments' => 'TEXT DEFAULT NULL',
                'qualityControlImages' => 'TEXT DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
        ],
        'finishedProduct' => [
            'store' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
            'query' => [
                'qualityControlTargetFolder' => 'VARCHAR(255) DEFAULT NULL',
                'qualityControlDocuments' => 'TEXT DEFAULT NULL',
                'qualityControlImages' => 'TEXT DEFAULT NULL',
                'qualityControlRemarks' => 'LONGTEXT DEFAULT NULL',
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Synchronize object tables for quality control fields';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof MySQLPlatform),
            'This migration is intended for MySQL/MariaDB.'
        );

        foreach (self::TABLES as $classId => $definitions) {
            $this->addMissingColumns($schema, 'object_store_' . $classId, $definitions['store']);
            $this->addMissingColumns($schema, 'object_query_' . $classId, $definitions['query']);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $classId => $definitions) {
            $this->dropColumns($schema, 'object_query_' . $classId, array_keys($definitions['query']));
            $this->dropColumns($schema, 'object_store_' . $classId, array_keys($definitions['store']));
        }
    }

    /**
     * @param array<string, string> $columns
     */
    private function addMissingColumns(Schema $schema, string $tableName, array $columns): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        foreach ($columns as $columnName => $columnDefinition) {
            if ($table->hasColumn($columnName)) {
                continue;
            }

            $this->addSql(sprintf(
                'ALTER TABLE `%s` ADD COLUMN `%s` %s',
                $tableName,
                $columnName,
                $columnDefinition
            ));
        }
    }

    /**
     * @param list<string> $columns
     */
    private function dropColumns(Schema $schema, string $tableName, array $columns): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        foreach ($columns as $columnName) {
            if (!$table->hasColumn($columnName)) {
                continue;
            }

            $this->addSql(sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $tableName, $columnName));
        }
    }
}
