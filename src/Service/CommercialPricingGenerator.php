<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ProductPricing;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Frame\Listing as FrameListing;
use Pimcore\Model\DataObject\SAPPricelist;
use Pimcore\Model\DataObject\SAPPricelist\Listing as SAPPricelistListing;
use Pimcore\Model\User;

final class CommercialPricingGenerator
{
    private const DESCENDANT_FRAME_CONDITION = 'oo_id IN (SELECT id FROM objects WHERE path LIKE ?)';

    /**
     * @return array{updated: list<array{id: int, path: string}>, errors: list<string>, pricelistCount: int}
     */
    public function generate(Concrete $source, ?int $submittedBasePrice, User $user): array
    {
        if (!$source instanceof Family && !$source instanceof Frame) {
            throw new \InvalidArgumentException('Pricing can only be generated from a Family or Frame.');
        }

        $basePrice = $submittedBasePrice ?? $this->readBasePrice($source);
        if ($basePrice === null || $basePrice < 0) {
            return ['updated' => [], 'errors' => ['Set a non-negative integer base price first.'], 'pricelistCount' => 0];
        }

        $pricelists = $this->commercialPricelists();
        if ($pricelists === []) {
            return [
                'updated' => [],
                'errors' => ['No commercial pricelists with a non-zero base factor were found.'],
                'pricelistCount' => 0,
            ];
        }

        if ($submittedBasePrice !== null) {
            $source->setBasePrice($submittedBasePrice);
        }

        if ($source instanceof Family && $submittedBasePrice !== null) {
            try {
                $source->setUserModification($user->getId());
                $source->save();
            } catch (\Throwable $e) {
                return [
                    'updated' => [],
                    'errors' => ['Failed to save the Family base price: ' . $e->getMessage()],
                    'pricelistCount' => count($pricelists),
                ];
            }
        }

        $targets = $source instanceof Frame ? [$source] : $this->descendantFrames($source);
        $updated = [];
        $errors = [];

        foreach ($targets as $frame) {
            if (!$frame->isAllowed('save', $user)) {
                $errors[] = sprintf('No save permission for frame %s.', $frame->getRealFullPath());
                continue;
            }

            try {
                $frame->setPricing($this->buildPricing($basePrice, $pricelists));
                $frame->setUserModification($user->getId());
                $frame->save();
                $updated[] = [
                    'id' => (int) $frame->getId(),
                    'path' => $frame->getRealFullPath(),
                ];
            } catch (\Throwable $e) {
                $errors[] = sprintf('Failed to update frame %s: %s', $frame->getRealFullPath(), $e->getMessage());
            }
        }

        return ['updated' => $updated, 'errors' => $errors, 'pricelistCount' => count($pricelists)];
    }

    private function readBasePrice(Concrete $source): ?int
    {
        $value = $source->getValueForFieldName('basePrice');

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
    }

    /** @return list<SAPPricelist> */
    private function commercialPricelists(): array
    {
        $listing = new SAPPricelistListing();
        $listing->setUnpublished(true);
        $pricelists = [];

        foreach ($listing as $pricelist) {
            if (!$pricelist instanceof SAPPricelist
                || $pricelist->getValueForFieldName('commercialPricelist') !== true
                && (int) $pricelist->getValueForFieldName('commercialPricelist') !== 1) {
                continue;
            }

            $factor = $pricelist->getBaseFactor();
            if ($factor === null || (float) $factor === 0.0) {
                continue;
            }

            $pricelists[] = $pricelist;
        }

        usort($pricelists, static fn (SAPPricelist $a, SAPPricelist $b): int => strnatcasecmp(
            (string) ($a->getCode() ?? $a->getKey()),
            (string) ($b->getCode() ?? $b->getKey())
        ));

        return $pricelists;
    }

    /** @return list<Frame> */
    private function descendantFrames(Family $family): array
    {
        $listing = new FrameListing();
        $listing->setUnpublished(true);
        $listing->setCondition(
            self::DESCENDANT_FRAME_CONDITION,
            [rtrim($family->getRealFullPath(), '/') . '/%']
        );

        return array_values(array_filter(
            iterator_to_array($listing),
            static fn (mixed $item): bool => $item instanceof Frame
        ));
    }

    /**
     * @param list<SAPPricelist> $pricelists
     */
    private function buildPricing(int $basePrice, array $pricelists): Fieldcollection
    {
        $collection = new Fieldcollection();

        foreach ($pricelists as $pricelist) {
            $factor = (float) $pricelist->getBaseFactor();
            $item = new ProductPricing();
            $item->setMarket((string) ($pricelist->getName() ?: $pricelist->getKey()));
            $item->setPriceAmountOverride($basePrice * $factor);
            $item->setBasePriceMultiplier($factor);
            $item->setCurrency((string) ($pricelist->getCurrency() ?: 'EUR'));
            $item->setPricelist($pricelist);
            $collection->add($item);
        }

        return $collection;
    }
}
