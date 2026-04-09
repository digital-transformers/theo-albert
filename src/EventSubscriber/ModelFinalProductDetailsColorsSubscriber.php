<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Model as ModelObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ModelFinalProductDetailsColorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
            DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION => 'onPreUpdateValidationException',
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $model = $event->getObject();
        if (!$this->isModelObject($model)) {
            return;
        }

        $this->syncModelRelations($model);
    }

    public function onPreUpdateValidationException(DataObjectEvent $event): void
    {
        $model = $event->getObject();
        if (!$this->isModelObject($model)) {
            return;
        }

        $this->syncModelRelations($model);

        $validationExceptions = $event->getArgument('validationExceptions');
        if (!is_array($validationExceptions) || $validationExceptions === []) {
            return;
        }

        $remainingExceptions = [];

        foreach ($validationExceptions as $validationException) {
            $message = $validationException instanceof \Throwable
                ? $validationException->getMessage()
                : '';

            if (str_contains($message, 'Passing relations without ID or type not allowed anymore!')) {
                continue;
            }

            $remainingExceptions[] = $validationException;
        }

        $event->setArgument('validationExceptions', $remainingExceptions);
    }

    private function isModelObject(mixed $object): bool
    {
        return $object instanceof ModelObject
            && method_exists($object, 'getClassName')
            && strtolower((string) $object->getClassName()) === 'model';
    }

    private function syncModelRelations(ModelObject $model): void
    {
        $collection = $model->getFinalProductDetails();
        if ($collection instanceof Fieldcollection) {
            foreach ($collection as $item) {
                if (!$item || $item->getType() !== 'finalProductProcess') {
                    continue;
                }

                $colorIds = $this->normalizeColorIds($this->getFieldValue($item, 'colors'));
                $this->setFieldValue(
                    $item,
                    'composingColors',
                    $this->resolveSyncedComposingColors(
                        $colorIds,
                        $this->getFieldValue($item, 'composingColors')
                    )
                );
                $this->setFieldValue(
                    $item,
                    'components',
                    $this->buildRelationMetadataFromSource(
                        $this->getFieldValue($item, 'components'),
                        'components',
                        []
                    )
                );
            }
        }

        $finalProducts = $model->getFinalProducts();
        if (is_array($finalProducts)) {
            $model->setFinalProducts($this->resolveFramesFromSource($finalProducts));
        }
    }

    /**
     * @param list<int|string> $colorIds
     *
     * @return list<ObjectMetadata>
     */
    private function resolveSyncedComposingColors(array $colorIds, mixed $currentValue): array
    {
        $expandedColors = $this->buildColorMetadataFromIds($colorIds);
        $currentColors = $this->buildColorMetadataFromSource($currentValue);

        return $this->preserveCurrentOrderIfSameSet($expandedColors, $currentColors);
    }

    /**
     * @return list<int|string>
     */
    private function normalizeColorIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = $value === '' ? [] : explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = is_array($item) ? ($item['id'] ?? $item['value'] ?? null) : $item;
            if (!is_scalar($id)) {
                continue;
            }

            $normalizedId = trim((string) $id);
            if ($normalizedId === '' || in_array($normalizedId, $ids, true)) {
                continue;
            }

            $ids[] = $normalizedId;
        }

        return $ids;
    }

    /**
     * @param list<int|string> $colorIds
     *
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromIds(array $colorIds): array
    {
        $metadata = [];
        $seen = [];

        foreach ($colorIds as $colorId) {
            $color = Color::getById((int) $colorId, ['force' => true]);
            if (!$color instanceof Color) {
                continue;
            }

            foreach ($this->expandColor($color) as $expandedColor) {
                $expandedColorId = (int) $expandedColor->getId();
                if ($expandedColorId < 1 || isset($seen[$expandedColorId])) {
                    continue;
                }

                $item = new ObjectMetadata('composingColors', ['name', 'relevant'], $expandedColor);
                $item->setName($this->normalizeString($expandedColor->getName()));
                $item->setRelevant(true);
                $metadata[] = $item;
                $seen[$expandedColorId] = true;
            }
        }

        return $metadata;
    }

    /**
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromSource(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $metadata = [];
        $seen = [];

        foreach ($value as $item) {
            $color = null;
            $name = null;
            $relevant = null;

            if ($item instanceof ObjectMetadata) {
                $object = $item->getObject();
                $color = $object instanceof Color ? $object : null;
                $name = $item->getName();
                $relevant = $item->getRelevant();
            } elseif (is_array($item) && is_scalar($item['id'] ?? null) && (int) $item['id'] > 0) {
                $object = Color::getById((int) $item['id'], ['force' => true]);
                $color = $object instanceof Color ? $object : null;
                $name = $item['name'] ?? null;
                $relevant = $item['relevant'] ?? null;
            } elseif ($item instanceof Color) {
                $color = $item;
            }

            if (!$color instanceof Color) {
                continue;
            }

            $colorId = (int) $color->getId();
            if ($colorId < 1 || isset($seen[$colorId])) {
                continue;
            }

            $metadataItem = new ObjectMetadata('composingColors', ['name', 'relevant'], $color);
            $metadataItem->setName($this->normalizeString($name) !== '' ? $this->normalizeString($name) : $this->normalizeString($color->getName()));
            $metadataItem->setRelevant($relevant === null ? true : (bool) $relevant);
            $metadata[] = $metadataItem;
            $seen[$colorId] = true;
        }

        return $metadata;
    }

    /**
     * @param list<ObjectMetadata>|mixed $value
     * @param list<string> $columns
     *
     * @return list<ObjectMetadata>
     */
    private function buildRelationMetadataFromSource(mixed $value, string $fieldName, array $columns): array
    {
        return $this->buildRelationMetadataFromObjects(
            $this->resolveObjectsFromSource($value),
            $fieldName,
            $columns
        );
    }

    /**
     * @param list<Concrete> $objects
     * @param list<string> $columns
     *
     * @return list<ObjectMetadata>
     */
    private function buildRelationMetadataFromObjects(array $objects, string $fieldName, array $columns): array
    {
        $metadata = [];
        $seen = [];

        foreach ($objects as $object) {
            $objectId = (int) $object->getId();
            if ($objectId < 1 || isset($seen[$objectId])) {
                continue;
            }

            $metadata[] = new ObjectMetadata($fieldName, $columns, $object);
            $seen[$objectId] = true;
        }

        return $metadata;
    }

    /**
     * @return list<Concrete>
     */
    private function resolveObjectsFromSource(mixed $value, ?callable $supports = null): array
    {
        if (!is_array($value)) {
            return [];
        }

        $objects = [];
        $seen = [];
        foreach ($value as $item) {
            $object = null;
            if ($item instanceof ObjectMetadata) {
                $object = $item->getObject();
            } elseif ($item instanceof Concrete) {
                $object = $item;
            } elseif (is_array($item) && is_scalar($item['id'] ?? null) && (int) $item['id'] > 0) {
                $object = DataObject::getById((int) $item['id'], ['force' => true]);
            }

            if (!$object instanceof Concrete) {
                continue;
            }

            if ($supports !== null && !$supports($object)) {
                continue;
            }

            $objectId = (int) $object->getId();
            if ($objectId < 1 || isset($seen[$objectId])) {
                continue;
            }

            $objects[] = $object;
            $seen[$objectId] = true;
        }

        return $objects;
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return list<Frame>
     */
    private function resolveFramesFromSource(array $value): array
    {
        $frames = [];
        $seen = [];

        foreach ($value as $item) {
            $frame = null;
            if ($item instanceof Frame) {
                $frame = $item;
            } elseif (is_array($item) && is_scalar($item['id'] ?? null) && (int) $item['id'] > 0) {
                $candidate = Frame::getById((int) $item['id'], ['force' => true]);
                $frame = $candidate instanceof Frame ? $candidate : null;
            }

            if (!$frame instanceof Frame) {
                continue;
            }

            $frameId = (int) $frame->getId();
            if ($frameId < 1 || isset($seen[$frameId])) {
                continue;
            }

            $frames[] = $frame;
            $seen[$frameId] = true;
        }

        return $frames;
    }

    /**
     * @param list<ObjectMetadata> $left
     * @param list<ObjectMetadata> $right
     */
    private function hasSameRelatedObjectIds(array $left, array $right): bool
    {
        return $this->extractRelatedObjectIds($left) === $this->extractRelatedObjectIds($right);
    }

    /**
     * @param list<ObjectMetadata> $expandedColors
     * @param list<ObjectMetadata> $currentColors
     *
     * @return list<ObjectMetadata>
     */
    private function preserveCurrentOrderIfSameSet(array $expandedColors, array $currentColors): array
    {
        if ($expandedColors === []) {
            return [];
        }

        if ($currentColors === []) {
            return $expandedColors;
        }

        return $this->hasSameRelatedObjectIds($expandedColors, $currentColors)
            ? $currentColors
            : $expandedColors;
    }

    /**
     * @param list<ObjectMetadata> $metadata
     *
     * @return list<int>
     */
    private function extractRelatedObjectIds(array $metadata): array
    {
        $ids = [];

        foreach ($metadata as $item) {
            $objectId = (int) $item->getObjectId();
            if ($objectId < 1) {
                $object = $item->getObject();
                if (!$object instanceof Concrete) {
                    continue;
                }

                $objectId = (int) $object->getId();
            }

            if ($objectId > 0) {
                $ids[] = $objectId;
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * @return list<Color>
     */
    private function expandColor(Color $color): array
    {
        $multiColors = $color->getMultiColor(['unpublished' => true]) ?: [];
        $expandedColors = [];

        foreach ($multiColors as $multiColor) {
            if ($multiColor instanceof Color) {
                $expandedColors[] = $multiColor;
            }
        }

        return $expandedColors !== [] ? $expandedColors : [$color];
    }

    private function getFieldValue(object|null $object, string $fieldName): mixed
    {
        if ($object === null) {
            return null;
        }

        $getter = 'get' . ucfirst($fieldName);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        if (method_exists($object, 'getObjectVar')) {
            return $object->getObjectVar($fieldName);
        }

        return null;
    }

    private function setFieldValue(object $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);
        if (method_exists($object, $setter)) {
            $object->$setter($value);

            return;
        }

        if (method_exists($object, 'setObjectVar')) {
            $object->setObjectVar($fieldName, $value);
        }
    }

    private function normalizeString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
