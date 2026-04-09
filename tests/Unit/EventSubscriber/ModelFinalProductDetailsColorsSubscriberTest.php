<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ModelFinalProductDetailsColorsSubscriber;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

final class ModelFinalProductDetailsColorsSubscriberTest extends Unit
{
    public function testPreserveCurrentOrderIfSameSetKeepsCurrentOrder(): void
    {
        $subscriber = new ModelFinalProductDetailsColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'preserveCurrentOrderIfSameSet');

        $expanded = [
            $this->createColorMetadata(184),
            $this->createColorMetadata(508),
        ];
        $current = [
            $this->createColorMetadata(508),
            $this->createColorMetadata(184),
        ];

        $result = $method->invoke($subscriber, $expanded, $current);

        self::assertSame([508, 184], $this->extractMetadataIds($result));
    }

    public function testPreserveCurrentOrderIfSameSetUsesExpandedOrderWhenSetChanges(): void
    {
        $subscriber = new ModelFinalProductDetailsColorsSubscriber();
        $method = new \ReflectionMethod($subscriber, 'preserveCurrentOrderIfSameSet');

        $expanded = [
            $this->createColorMetadata(184),
            $this->createColorMetadata(508),
        ];
        $current = [
            $this->createColorMetadata(508),
            $this->createColorMetadata(999),
        ];

        $result = $method->invoke($subscriber, $expanded, $current);

        self::assertSame([184, 508], $this->extractMetadataIds($result));
    }

    private function createColorMetadata(int $id): ObjectMetadata
    {
        $metadata = new ObjectMetadata('composingColors', ['name', 'relevant']);
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
