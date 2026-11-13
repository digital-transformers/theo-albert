<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\FrameIdentityFormatter;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

final class FrameIdentityFormatterTest extends Unit
{
    public function testFrameCodeUsesRawMainColor(): void
    {
        self::assertSame('BALL -5', FrameIdentityFormatter::code('BALL', '5'));
        self::assertSame('BALL -36', FrameIdentityFormatter::code('BALL', '36'));
        self::assertSame('APRE 304', FrameIdentityFormatter::code('APRE', '304'));
    }

    public function testNamesFollowTheFourExamples(): void
    {
        $purple = $this->color('304', 'Fluo Purple');
        $yellow = $this->color('303', 'Fluo Yellow');
        $black = $this->color('7367', 'Citrus Black');
        $lavender = $this->color('79', 'Lavender Matte');
        $bluesy = $this->color('765', 'Bluesy Purple');
        $grey = $this->color('TLPDR 5008', 'Rocky Grey', true);
        $orchid = $this->color('AB2436', 'Transparent Orchid', true, true);

        self::assertSame('A PRESENT 304 FLUO PURPLE', FrameIdentityFormatter::name('A Present', '304', [$purple]));
        self::assertSame('BALLOON 016 FLUO YELLOW + ROCKY GREY', FrameIdentityFormatter::name('Balloon', '16', [$yellow, $grey]));
        self::assertSame('ISOTHERM 378 C 7367 + 303', FrameIdentityFormatter::name('Isotherm', '378', [$black, $yellow]));
        self::assertSame('ELVIS 244 C 765 + 79 + TRANSPARENT ORCHID', FrameIdentityFormatter::name('Elvis', '244', [$bluesy, $lavender, $orchid, $grey]));
        self::assertSame('BALLOON 005 FLUO YELLOW', FrameIdentityFormatter::name('Balloon', '5', [$yellow]));
    }

    private function color(string $code, string $label, bool $acetate = false, bool $relevant = false): ObjectMetadata
    {
        $color = new Color();
        $color->setCode($code);
        $color->setName($label);
        $color->setColorType($acetate ? 'acetate' : 'metal-single-color');
        $metadata = $this->getMockBuilder(ObjectMetadata::class)
            ->setConstructorArgs(['composedColors', ['name', 'relevant']])
            ->onlyMethods(['getObject'])
            ->getMock();
        $metadata->method('getObject')->willReturn($color);
        $metadata->setRelevant($relevant);

        return $metadata;
    }
}
