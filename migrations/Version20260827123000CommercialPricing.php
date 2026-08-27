<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827123000CommercialPricing extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve legacy Family/Frame base prices as integers and normalize pricing currencies to ISO codes.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->convertLegacyBasePrices(
            'object_collection_productPricing_family',
            ['object_store_family', 'object_query_family']
        );
        $this->convertLegacyBasePrices(
            'object_collection_productPricing_finishedProduct',
            ['object_store_finishedProduct', 'object_query_finishedProduct']
        );

        foreach ($this->pricingTables() as $table) {
            $this->connection->executeStatement(sprintf(
                "UPDATE `%s` SET currency = CASE LOWER(currency) WHEN 'euro' THEN 'EUR' WHEN 'gbp' THEN 'GBP' WHEN 'usd' THEN 'USD' ELSE UPPER(currency) END WHERE currency IS NOT NULL",
                str_replace('`', '``', $table)
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The original fieldcollection base prices cannot be reconstructed.');
    }

    /** @param list<string> $targetTables */
    private function convertLegacyBasePrices(string $table, array $targetTables): void
    {
        if (!$this->tableExists($table)) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(sprintf(
            "SELECT id, priceAmountOverride FROM `%s` WHERE fieldname = 'basePrice' AND priceAmountOverride IS NOT NULL ORDER BY id, `index`",
            str_replace('`', '``', $table)
        ));
        foreach ($targetTables as $targetTable) {
            $this->ensureBasePriceColumn($targetTable);
        }

        $converted = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if ($id < 1 || isset($converted[$id])) {
                continue;
            }

            $basePrice = (int) round((float) $row['priceAmountOverride']);
            foreach ($targetTables as $targetTable) {
                $this->connection->executeStatement(
                    sprintf('UPDATE `%s` SET basePrice = ? WHERE oo_id = ?', str_replace('`', '``', $targetTable)),
                    [$basePrice, $id]
                );
            }

            $converted[$id] = true;
        }

        $this->connection->executeStatement(sprintf(
            "DELETE FROM `%s` WHERE fieldname = 'basePrice'",
            str_replace('`', '``', $table)
        ));
        $this->write(sprintf('Converted %d legacy base price(s) from %s.', count($converted), $table));
    }

    /** @return list<string> */
    private function pricingTables(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['TABLE_NAME'],
            $this->connection->fetchAllAssociative(
                "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'object_collection_productPricing_%'"
            )
        );
    }

    private function tableExists(string $table): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
    }

    private function ensureBasePriceColumn(string $table): void
    {
        if (!$this->tableExists($table)) {
            throw new \RuntimeException(sprintf('Expected data object table %s was not found.', $table));
        }

        $exists = (bool) $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, 'basePrice']
        );
        if ($exists) {
            return;
        }

        $this->connection->executeStatement(sprintf(
            'ALTER TABLE `%s` ADD COLUMN basePrice BIGINT(20) DEFAULT NULL',
            str_replace('`', '``', $table)
        ));
    }
}
