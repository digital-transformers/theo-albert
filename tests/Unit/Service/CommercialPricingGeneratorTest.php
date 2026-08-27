<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CommercialPricingGenerator;
use Codeception\Test\Unit;

final class CommercialPricingGeneratorTest extends Unit
{
    public function testDescendantFrameConditionUsesCanonicalObjectsPath(): void
    {
        $reflection = new \ReflectionClass(CommercialPricingGenerator::class);

        self::assertSame(
            'oo_id IN (SELECT id FROM objects WHERE path LIKE ?)',
            $reflection->getConstant('DESCENDANT_FRAME_CONDITION')
        );
    }
}
