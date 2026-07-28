<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataImporter;

use App\DataImporter\AttributeWithTrimFallbackStrategy;
use Codeception\Test\Unit;

final class AttributeWithTrimFallbackStrategyTest extends Unit
{
    public function testTriesTheTrimmedIdentifierBeforeItsExactFallback(): void
    {
        $strategy = (new \ReflectionClass(AttributeWithTrimFallbackStrategy::class))
            ->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($strategy, 'identifierCandidates');

        self::assertSame(['MATT 60', '  MATT 60'], $method->invoke($strategy, '  MATT 60'));
        self::assertSame(['AB1882', 'AB1882  '], $method->invoke($strategy, 'AB1882  '));
        self::assertSame(['AB2885'], $method->invoke($strategy, 'AB2885'));
        self::assertSame([4300], $method->invoke($strategy, 4300));
    }
}
