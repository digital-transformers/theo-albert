<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SapSyncResolver;
use Codeception\Test\Unit;

final class SapSyncResolverTest extends Unit
{
    public function testFrameComponentsOverrideModelComponentsByType(): void
    {
        $resolver = new SapSyncResolver();
        $model = [
            ['id' => 'model-metal', 'types' => ['metal']],
            ['id' => 'model-color', 'types' => ['color']],
            ['id' => 'model-untyped', 'types' => []],
        ];
        $frame = [
            ['id' => 'frame-color', 'types' => ['COLOR']],
            ['id' => 'frame-acetate', 'types' => ['acetate']],
        ];

        $result = $resolver->effectiveComponents(
            $model,
            $frame,
            static fn (array $component): array => $component['types'],
        );

        self::assertSame(
            ['model-metal', 'frame-color', 'model-untyped', 'frame-acetate'],
            array_column($result, 'id'),
        );
    }

    public function testLeadTimeUsesMostSpecificNonBlankValue(): void
    {
        $resolver = new SapSyncResolver();

        self::assertSame(4.0, $resolver->leadTime(null, '', 4, 6, 8));
        self::assertSame(0.0, $resolver->leadTime(0, 3, 4, 6, 8));
        self::assertNull($resolver->leadTime(null, null, null, null, null));
    }
}
