<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\SAPPricelistCurrencyResolver;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Model\DataObject\SAPPricelist;
use Pimcore\Model\DataObject\SAPPricelist\Listing as SAPPricelistListing;

final class Version20260827114000NormalizeSAPPricelistCurrencies extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set SAP pricelist currencies from their keys and default empty rounding values to no.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $resolver = new SAPPricelistCurrencyResolver();
        $listing = new SAPPricelistListing();
        $listing->setUnpublished(true);

        $seen = 0;
        $updated = 0;
        foreach ($listing as $priceList) {
            if (!$priceList instanceof SAPPricelist) {
                continue;
            }

            ++$seen;
            $currency = $resolver->resolve((string) $priceList->getKey());
            $changed = false;
            if ($priceList->getCurrency() !== $currency) {
                $priceList->setCurrency($currency);
                $changed = true;
            }
            if (trim((string) $priceList->getRounding()) === '') {
                $priceList->setRounding('no');
                $changed = true;
            }
            if (!$changed) {
                continue;
            }

            $priceList->save();
            ++$updated;
        }

        $this->write(sprintf('Normalized %d of %d SAP pricelists.', $updated, $seen));
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Previous SAP pricelist currencies cannot be reconstructed.');
    }
}
