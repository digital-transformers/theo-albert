<?php

declare(strict_types=1);

namespace App\DataObject\Calculator;

use Pimcore\Model\DataObject\ClassDefinition\CalculatorClassInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;

final class ProductPricingAmountCalculator implements CalculatorClassInterface
{
    public function compute(Concrete $object, CalculatedValue $context): string
    {
        if ($context->getOwnerType() !== 'fieldcollection' || !method_exists($object, 'getPricing')) {
            return '';
        }

        $item = $object->getPricing()?->get((int) $context->getIndex());
        if ($item === null) {
            return '';
        }

        $override = method_exists($item, 'getPriceAmountOverride') ? $item->getPriceAmountOverride() : null;
        if ($override !== null && $override !== '') {
            return $this->format((float) $override);
        }

        $basePrice = method_exists($object, 'getBasePrice') ? $object->getBasePrice() : null;
        $multiplier = method_exists($item, 'getBasePriceMultiplier') ? $item->getBasePriceMultiplier() : null;
        if ($basePrice === null || $multiplier === null) {
            return '';
        }

        return $this->format((float) $basePrice * (float) $multiplier);
    }

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context): string
    {
        return $this->compute($object, $context);
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
