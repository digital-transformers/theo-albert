<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CommercialPricingGenerator;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ProductPricing;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\SAPPricelist;

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

    public function testBasePricelistChainsAreExpandedAndDeduplicated(): void
    {
        $europe = $this->pricelist(218, '1');
        $usd = $this->pricelist(468, '33', $europe);
        $customer = $this->pricelist(268, '51', $usd);
        $generator = new CommercialPricingGenerator();
        $method = new \ReflectionMethod($generator, 'expandBasePricelists');

        $expanded = $method->invoke($generator, [$usd, $customer]);

        self::assertSame([218, 468, 268], array_map(
            static fn (SAPPricelist $pricelist): int => (int) $pricelist->getId(),
            $expanded
        ));
    }

    public function testBasePricelistWithoutFactorUsesOne(): void
    {
        $base = $this->pricelist(218, '1');
        $base->method('getBaseFactor')->willReturn(null);
        $method = new \ReflectionMethod(new CommercialPricingGenerator(), 'effectiveFactor');

        self::assertSame(1.0, $method->invoke(new CommercialPricingGenerator(), $base));
    }

    /** @dataProvider frameBasePriceCases */
    public function testFrameBasePricePrecedence(?int $frameBase, ?int $familyBase, bool $usesOwn): void
    {
        $method = new \ReflectionMethod(new CommercialPricingGenerator(), 'frameUsesOwnBasePrice');

        self::assertSame($usesOwn, $method->invoke(new CommercialPricingGenerator(), $frameBase, $familyBase));
    }

    /** @return iterable<string, array{?int, ?int, bool}> */
    public static function frameBasePriceCases(): iterable
    {
        yield 'unset frame uses family' => [null, 100, false];
        yield 'same frame value uses family' => [100, 100, false];
        yield 'different frame value has priority' => [120, 100, true];
        yield 'frame value works without family' => [120, null, true];
    }

    public function testFamilyIsAssignedPricingDuringAutomaticSynchronization(): void
    {
        $family = $this->createMock(Family::class);
        $family->method('getValueForFieldName')->with('basePrice')->willReturn(100);
        $family->expects(self::once())->method('setPricing');

        (new CommercialPricingGeneratorForTest())->synchronizeBasePriceChange($family);
    }

    public function testProcessingGuardPreventsRecursiveSynchronization(): void
    {
        $generator = new CommercialPricingGeneratorForTest();
        $processing = new \ReflectionProperty(CommercialPricingGenerator::class, 'processing');
        $processing->setValue($generator, true);
        $frame = $this->createMock(Frame::class);
        $frame->expects(self::never())->method('setPricing');

        $generator->synchronizeBasePriceChange($frame);
    }

    public function testPricingItemsAreAttachedToTheirOwningObjectBeforeAssignment(): void
    {
        $generator = new CommercialPricingGenerator();
        $frame = $this->createMock(Frame::class);
        $item = new ProductPricing();
        $pricing = new Fieldcollection([$item]);
        $frame->expects(self::once())
            ->method('setPricing')
            ->with(self::callback(static fn (Fieldcollection $value): bool => $value->get(0)?->getObject() === $frame));

        (new \ReflectionMethod($generator, 'assignPricing'))->invoke($generator, $frame, $pricing);
    }

    private function pricelist(int $id, string $code, ?SAPPricelist $base = null): SAPPricelist
    {
        $pricelist = $this->createMock(SAPPricelist::class);
        $pricelist->method('getId')->willReturn($id);
        $pricelist->method('getCode')->willReturn($code);
        $pricelist->method('getBasePricelist')->willReturn($base);

        return $pricelist;
    }
}

final class CommercialPricingGeneratorForTest extends CommercialPricingGenerator
{
    protected function pricingPricelists(): array
    {
        return [];
    }

    protected function descendantFrames(Family $family): array
    {
        return [];
    }
}
