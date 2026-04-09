<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ModelFrameGenerator;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

final class ModelFrameGeneratorTest extends Unit
{
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
        $metadata = new ObjectMetadata('composedColors', ['name', 'relevant']);
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
