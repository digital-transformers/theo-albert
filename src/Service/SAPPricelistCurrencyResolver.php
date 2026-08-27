<?php
declare(strict_types=1);

namespace App\Service;

final class SAPPricelistCurrencyResolver
{
    public function resolve(string $key): string
    {
        if ($this->hasIsoToken($key, 'JPY') || str_contains($key, '¥')) {
            return 'JPY';
        }
        if ($this->hasIsoToken($key, 'CHF')) {
            return 'CHF';
        }
        if ($this->hasIsoToken($key, 'USD') || str_contains($key, '$')) {
            return 'USD';
        }
        if ($this->hasIsoToken($key, 'GBP') || str_contains($key, '£')) {
            return 'GBP';
        }
        if ($this->hasIsoToken($key, 'EUR') || str_contains($key, '€')) {
            return 'EUR';
        }

        return 'EUR';
    }

    private function hasIsoToken(string $key, string $code): bool
    {
        return preg_match(
            sprintf('/(?<![A-Z])%s(?![A-Z])/iu', preg_quote($code, '/')),
            $key
        ) === 1;
    }
}
