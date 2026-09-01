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

class CommercialPricingGenerator
{
    private const DESCENDANT_FRAME_CONDITION = 'oo_id IN (SELECT id FROM objects WHERE path LIKE ?)';

    private bool $processing = false;

    /**
     * @return array{updated: list<array{id: int, path: string}>, errors: list<string>, pricelistCount: int}
     */
    public function generate(Concrete $source, ?int $submittedBasePrice, User $user): array
    {
        if (!$source instanceof Family && !$source instanceof Frame) {
            throw new \InvalidArgumentException('Pricing can only be generated from a Family or Frame.');
        }
        if ($submittedBasePrice !== null && $submittedBasePrice < 0) {
            return ['updated' => [], 'errors' => ['Set a non-negative integer base price first.'], 'pricelistCount' => 0];
        }
        if ($this->processing) {
            return ['updated' => [], 'errors' => [], 'pricelistCount' => 0];
        }

        $this->processing = true;
        try {
            return $this->doGenerate($source, $submittedBasePrice, $user);
        } finally {
            $this->processing = false;
        }
    }

    public function synchronizeBasePriceChange(Family|Frame $source): void
    {
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        try {
            $pricelists = $this->pricingPricelists();
            if ($source instanceof Family) {
                $this->synchronizeFamily($source, $this->readBasePrice($source), $pricelists, false, null);

                return;
            }

            $this->assignPricing($source, $this->pricingForFrame($source, $pricelists));
        } finally {
            $this->processing = false;
        }
    }

    /**
     * @return array{updated: list<array{id: int, path: string}>, errors: list<string>, pricelistCount: int}
     */
    private function doGenerate(Concrete $source, ?int $submittedBasePrice, User $user): array
    {
        if ($submittedBasePrice !== null) {
            $source->setBasePrice($submittedBasePrice);
        }

        $pricelists = $this->pricingPricelists();
        if ($pricelists === []) {
            return [
                'updated' => [],
                'errors' => ['No commercial pricelists with a non-zero base factor were found.'],
                'pricelistCount' => 0,
            ];
        }

        if ($source instanceof Family) {
            $basePrice = $this->readBasePrice($source);
            if ($basePrice === null || $basePrice < 0) {
                return ['updated' => [], 'errors' => ['Set a non-negative integer base price first.'], 'pricelistCount' => count($pricelists)];
            }

            $result = $this->synchronizeFamily($source, $basePrice, $pricelists, true, $user);

            return [...$result, 'pricelistCount' => count($pricelists)];
        }

        $pricing = $this->pricingForFrame($source, $pricelists);
        if ($pricing->isEmpty()) {
            return [
                'updated' => [],
                'errors' => ['Set a base price on the Frame or its ancestor Family first.'],
                'pricelistCount' => count($pricelists),
            ];
        }
        if (!$source->isAllowed('save', $user)) {
            return [
                'updated' => [],
                'errors' => [sprintf('No save permission for frame %s.', $source->getRealFullPath())],
                'pricelistCount' => count($pricelists),
            ];
        }

        try {
            $this->assignPricing($source, $pricing);
            $source->setUserModification($user->getId());
            $source->save();
        } catch (\Throwable $e) {
            return [
                'updated' => [],
                'errors' => [sprintf('Failed to update frame %s: %s', $source->getRealFullPath(), $e->getMessage())],
                'pricelistCount' => count($pricelists),
            ];
        }

        return [
            'updated' => [['id' => (int) $source->getId(), 'path' => $source->getRealFullPath()]],
            'errors' => [],
            'pricelistCount' => count($pricelists),
        ];
    }

    /**
     * @param list<SAPPricelist> $pricelists
     *
     * @return array{updated: list<array{id: int, path: string}>, errors: list<string>}
     */
    private function synchronizeFamily(
        Family $family,
        ?int $familyBasePrice,
        array $pricelists,
        bool $saveFamily,
        ?User $user,
    ): array {
        $familyPricing = $familyBasePrice === null
            ? new Fieldcollection()
            : $this->buildPricing($familyBasePrice, $pricelists);
        $this->assignPricing($family, $familyPricing);
        $updated = [];
        $errors = [];

        if ($saveFamily) {
            try {
                if ($user instanceof User) {
                    $family->setUserModification($user->getId());
                }
                $family->save();
                $updated[] = ['id' => (int) $family->getId(), 'path' => $family->getRealFullPath()];
            } catch (\Throwable $e) {
                return [
                    'updated' => [],
                    'errors' => ['Failed to update the Family pricing: ' . $e->getMessage()],
                ];
            }
        }

        foreach ($this->descendantFrames($family) as $frame) {
            if ($user instanceof User && !$frame->isAllowed('save', $user)) {
                $errors[] = sprintf('No save permission for frame %s.', $frame->getRealFullPath());
                continue;
            }

            try {
                $this->assignPricing($frame, $this->pricingForFrame(
                    $frame,
                    $pricelists,
                    $family,
                    $familyBasePrice,
                    $familyPricing
                ));
                if ($user instanceof User) {
                    $frame->setUserModification($user->getId());
                }
                $frame->save();
                $updated[] = ['id' => (int) $frame->getId(), 'path' => $frame->getRealFullPath()];
            } catch (\Throwable $e) {
                $errors[] = sprintf('Failed to update frame %s: %s', $frame->getRealFullPath(), $e->getMessage());
            }
        }

        return ['updated' => $updated, 'errors' => $errors];
    }

    private function readBasePrice(Concrete $source): ?int
    {
        $value = $source->getValueForFieldName('basePrice');

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
    }

    /** @return list<SAPPricelist> */
    protected function pricingPricelists(): array
    {
        $listing = new SAPPricelistListing();
        $listing->setUnpublished(true);
        $commercial = [];

        foreach ($listing as $pricelist) {
            if (!$pricelist instanceof SAPPricelist || !$this->isCommercial($pricelist)) {
                continue;
            }
            $factor = $pricelist->getBaseFactor();
            if ($factor === null || (float) $factor === 0.0) {
                continue;
            }
            $commercial[] = $pricelist;
        }

        return $this->expandBasePricelists($commercial);
    }

    private function isCommercial(SAPPricelist $pricelist): bool
    {
        $value = $pricelist->getValueForFieldName('commercialPricelist');

        return $value === true || (int) $value === 1;
    }

    /**
     * @param list<SAPPricelist> $pricelists
     *
     * @return list<SAPPricelist>
     */
    private function expandBasePricelists(array $pricelists): array
    {
        $expanded = [];

        foreach ($pricelists as $pricelist) {
            $current = $pricelist;
            $chain = [];
            while ($current instanceof SAPPricelist) {
                $id = (int) $current->getId();
                if ($id < 1 || isset($chain[$id])) {
                    break;
                }
                $chain[$id] = true;
                $expanded[$id] ??= $current;

                $base = $current->getBasePricelist();
                $current = $base instanceof SAPPricelist ? $base : null;
            }
        }

        $expanded = array_values($expanded);
        usort($expanded, static fn (SAPPricelist $a, SAPPricelist $b): int => strnatcasecmp(
            (string) ($a->getCode() ?? $a->getKey()),
            (string) ($b->getCode() ?? $b->getKey())
        ));

        return $expanded;
    }

    /** @return list<Frame> */
    protected function descendantFrames(Family $family): array
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
    private function pricingForFrame(
        Frame $frame,
        array $pricelists,
        ?Family $knownFamily = null,
        ?int $knownFamilyBasePrice = null,
        ?Fieldcollection $knownFamilyPricing = null,
    ): Fieldcollection {
        $family = $knownFamily ?? $this->ancestorFamily($frame);
        $familyBasePrice = $knownFamily instanceof Family
            ? $knownFamilyBasePrice
            : ($family instanceof Family ? $this->readBasePrice($family) : null);
        $frameBasePrice = $this->readBasePrice($frame);

        if ($this->frameUsesOwnBasePrice($frameBasePrice, $familyBasePrice)) {
            return $this->buildPricing($frameBasePrice, $pricelists);
        }

        if ($family instanceof Family) {
            $familyPricing = $knownFamilyPricing ?? $family->getPricing();
            if ($familyPricing instanceof Fieldcollection && !$familyPricing->isEmpty()) {
                return $this->copyPricing($familyPricing);
            }
            if ($familyBasePrice !== null) {
                return $this->buildPricing($familyBasePrice, $pricelists);
            }
        }

        return $frameBasePrice === null
            ? new Fieldcollection()
            : $this->buildPricing($frameBasePrice, $pricelists);
    }

    private function frameUsesOwnBasePrice(?int $frameBasePrice, ?int $familyBasePrice): bool
    {
        return $frameBasePrice !== null && ($familyBasePrice === null || $frameBasePrice !== $familyBasePrice);
    }

    private function ancestorFamily(Frame $frame): ?Family
    {
        $parent = $frame->getParent();
        while ($parent instanceof Concrete) {
            if ($parent instanceof Family) {
                return $parent;
            }
            $parent = $parent->getParent();
        }

        return null;
    }

    /**
     * @param list<SAPPricelist> $pricelists
     */
    private function buildPricing(int $basePrice, array $pricelists): Fieldcollection
    {
        $collection = new Fieldcollection();
        foreach ($pricelists as $pricelist) {
            $collection->add($this->buildPricingItem($pricelist, $basePrice));
        }

        return $collection;
    }

    private function buildPricingItem(SAPPricelist $pricelist, int $basePrice): ProductPricing
    {
        $factor = $this->effectiveFactor($pricelist);
        $item = new ProductPricing();
        $item->setMarket((string) ($pricelist->getName() ?: $pricelist->getKey()));
        $item->setPriceAmountOverride($this->roundPrice($basePrice * $factor, $pricelist->getRounding()));
        $item->setBasePriceMultiplier($factor);
        $item->setCurrency((string) ($pricelist->getCurrency() ?: 'EUR'));
        $item->setPricelist($pricelist);

        return $item;
    }

    private function effectiveFactor(SAPPricelist $pricelist): float
    {
        $factor = $pricelist->getBaseFactor();

        return $factor === null || (float) $factor === 0.0 ? 1.0 : (float) $factor;
    }

    private function roundPrice(float $price, ?string $rounding): float
    {
        $step = match ($rounding) {
            'upper_1' => 1.0,
            'upper_5' => 5.0,
            default => null,
        };

        return $step === null ? $price : ceil(($price - 1e-9) / $step) * $step;
    }

    private function copyPricing(Fieldcollection $source): Fieldcollection
    {
        $copy = new Fieldcollection();
        foreach ($source as $item) {
            if (!$item instanceof ProductPricing) {
                continue;
            }

            $clone = new ProductPricing();
            $clone->setMarket($item->getMarket());
            $clone->setPriceAmountOverride($item->getPriceAmountOverride());
            $clone->setBasePriceMultiplier($item->getBasePriceMultiplier());
            $clone->setCurrency($item->getCurrency());
            $clone->setPricelist($item->getPricelist());
            $copy->add($clone);
        }

        return $copy;
    }

    private function assignPricing(Family|Frame $object, Fieldcollection $pricing): void
    {
        $pricing->setObject($object);
        $object->setPricing($pricing);
    }
}
