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

    public function testExistingFrameNameIsPreserved(): void
    {
        $subscriber = new FrameNameFromComposedColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'shouldGenerateName');

        self::assertFalse($method->invoke($subscriber, 'Custom frame name', 'Generated name 184'));
    }

    public function testBlankFrameNameIsGenerated(): void
    {
        $subscriber = new FrameNameFromComposedColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'shouldGenerateName');

        self::assertTrue($method->invoke($subscriber, '', 'Generated name 184'));
        self::assertFalse($method->invoke($subscriber, '', ''));
    }
}
