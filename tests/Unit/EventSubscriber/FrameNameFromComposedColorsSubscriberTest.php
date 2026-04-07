<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\FrameNameFromComposedColorsSubscriber;
use Codeception\Test\Unit;

final class FrameNameFromComposedColorsSubscriberTest extends Unit
{
    public function testStripTrailingColorCodesHandlesPreviousPlusSeparatedOrder(): void
    {
        $subscriber = new FrameNameFromComposedColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'stripTrailingColorCodes');

        $baseName = $method->invoke($subscriber, 'WHOOHOO 184 +  508', ['508', '184']);

        self::assertSame('WHOOHOO', $baseName);
    }

    public function testFormatColorCodesUsesPlusSeparator(): void
    {
        $subscriber = new FrameNameFromComposedColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'formatColorCodes');

        $suffix = $method->invoke($subscriber, ['508', '184']);

        self::assertSame('508 + 184', $suffix);
    }
}
