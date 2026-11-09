<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ModelFrameGenerator;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

final class ModelFrameGeneratorTest extends Unit
{
    public function testLegacyColorMetadataWithoutSapColumnsCanBeExpanded(): void
    {
        $color = new Color();
        $color->setId(184);
        $source = $this->getMockBuilder(ObjectMetadata::class)
            ->setConstructorArgs(['composingColors', ['name', 'relevant']])
            ->onlyMethods(['getObject'])
            ->getMock();
        $source->method('getObject')->willReturn($color);
        $source->setName('Legacy colour');
        $source->setRelevant(false);

        $generator = new ModelFrameGenerator();
        $method = new \ReflectionMethod($generator, 'buildColorMetadataFromSource');
        $result = $method->invoke($generator, [$source]);

        self::assertCount(1, $result);
        self::assertSame('Legacy colour', $result[0]->getName());
        self::assertFalse($result[0]->getRelevant());
        self::assertNull($result[0]->getSapColorCode());
        self::assertSame(1, $result[0]->getOrderNr());
        self::assertNull($result[0]->getQty());
    }

    public function testPreserveCurrentOrderIfSameSetKeepsCurrentOrder(): void
    {
        $generator = new ModelFrameGenerator();
        $method = new \ReflectionMethod($generator, 'preserveCurrentOrderIfSameSet');

        $expanded = [
            $this->createColorMetadata(184),
            $this->createColorMetadata(508),
        ];
        $current = [
            $this->createColorMetadata(508),
            $this->createColorMetadata(184),
        ];

        $result = $method->invoke($generator, $expanded, $current);

        self::assertSame([508, 184], $this->extractMetadataIds($result));
    }

    private function createColorMetadata(int $id): ObjectMetadata
    {
        $metadata = new ObjectMetadata('composedColors', ['name', 'relevant', 'sapColorCode', 'orderNr', 'comment', 'qty']);
        $metadata->setObjectId($id);
        $metadata->setName((string) $id);
        $metadata->setRelevant(true);

        return $metadata;
    }

    /**
     * @param list<ObjectMetadata> $metadata
     *
     * @return list<int>
     */
    private function extractMetadataIds(array $metadata): array
    {
        return array_map(
            static fn (ObjectMetadata $item): int => $item->getObjectId(),
            $metadata
        );
    }
}
