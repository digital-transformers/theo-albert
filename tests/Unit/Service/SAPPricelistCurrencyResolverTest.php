<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SAPPricelistCurrencyResolver;
use Codeception\Test\Unit;

final class SAPPricelistCurrencyResolverTest extends Unit
{
    /** @dataProvider keys */
    public function testResolvesExplicitCurrencyAndDefaultsToEuro(string $key, string $expected): void
    {
        self::assertSame($expected, (new SAPPricelistCurrencyResolver())->resolve($key));
    }

    /** @return iterable<string, array{string, string}> */
    public static function keys(): iterable
    {
        yield 'JPY code' => ['Price list Japan in JPY|32', 'JPY'];
        yield 'yen symbol' => ['Price list Japan ¥|32', 'JPY'];
        yield 'CHF code' => ['Price list Switzerland in CHF|34', 'CHF'];
        yield 'USD code' => ['Price list USA in USD|33', 'USD'];
        yield 'dollar symbol' => ['Price list Cases $|18', 'USD'];
        yield 'GBP code' => ['Price list UK in GBP|60', 'GBP'];
        yield 'pound symbol' => ['Price list Cases £|61', 'GBP'];
        yield 'EUR code' => ['Price list Europe in EUR|62', 'EUR'];
        yield 'euro symbol' => ['Price list Cases €|17', 'EUR'];
        yield 'country name is not a currency' => ['Price list Voorlopig Japan|16', 'EUR'];
        yield 'no currency marker' => ['Price list Customers Ramassia|51', 'EUR'];
    }
}
