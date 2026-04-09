<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Frame\Listing as FrameListing;
use Pimcore\Model\DataObject\Model as ModelObject;
use Pimcore\Model\DataObject\Supplier;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\User;

final class ModelFrameGenerator
{
    /**
     * @param list<array<string, mixed>>|null $submittedDetails
     * @param list<array<string, mixed>>|null $submittedFinalProducts
     * @param array{frameBaseCode?: mixed, name?: mixed}|null $submittedModelBaseData
     *
     * @return array{created: list<array{id: int, code: string, name: string, path: string}>, skipped: list<array{code: string, reason: string}>, errors: list<string>}
     */
    public function generate(
        ModelObject $model,
        ?array $submittedDetails = null,
        ?User $user = null,
        ?array $submittedFinalProducts = null,
        ?array $submittedModelBaseData = null,
    ): array {
        $created = [];
        $createdFrames = [];
        $skipped = [];
        $errors = [];
        $baseFrameCode = $this->resolveModelBaseFrameCode($model, $submittedModelBaseData);
        $baseName = $this->resolveModelBaseName($model, $submittedModelBaseData);

        foreach ($this->resolveDetails($model, $submittedDetails) as $index => $detail) {
            $mainColorCode = $this->normalizeString($detail['mainColorCode'] ?? null);
            $supplier = $detail['supplier'] ?? null;
            $composedColors = $detail['composedColors'] ?? [];
            $components = $detail['components'] ?? [];

            if ($mainColorCode === '') {
                $skipped[] = [
                    'code' => $baseFrameCode,
                    'reason' => sprintf('Fieldcollection item %d has no main color code', $index + 1),
                ];
                continue;
            }

            $code = $this->joinNonEmpty([$baseFrameCode, $mainColorCode], ' ');
            $name = $this->buildFrameName($baseName, $composedColors, $mainColorCode);

            if ($code === '') {
                $errors[] = sprintf('Fieldcollection item %d has no generated frame code', $index + 1);
                continue;
            }

            if ($this->findExistingChildFrame($model, $code) instanceof Frame) {
                $skipped[] = [
                    'code' => $code,
                    'reason' => 'A child frame with this code already exists',
                ];
                continue;
            }

            try {
                $step = 'instantiate frame';
                $frame = new Frame();
                $step = 'set parent';
                $frame->setParent($model);
                $step = 'set key';
                $frame->setKey($this->buildUniqueKey($model, $code, $name, $index, $mainColorCode));
                $step = 'set published';
                $frame->setPublished(false);
                $step = 'set code';
                $this->setFieldValue($frame, 'code', $code);
                $step = 'set name';
                $this->setFieldValue($frame, 'name', $name);
                $step = 'set supplier';
                $this->setFieldValue($frame, 'supplier', $supplier instanceof Supplier ? $supplier : null);
                $step = 'set composedColors';
                $this->setFieldValue($frame, 'composedColors', $composedColors);
                $step = 'set components';
                $this->setFieldValue($frame, 'components', $components);

                $step = 'set artBase';
                $this->setFieldValue($frame, 'artBase', $model);

                $step = 'set mainColorCode';
                $this->setFieldValue($frame, 'mainColorCode', $mainColorCode);

                if ($user instanceof User) {
                    $step = 'set ownership';
                    $frame->setUserOwner($user->getId());
                    $frame->setUserModification($user->getId());
                }

                $step = 'save frame';
                $frame->save();

                $step = 'build result payload';
                $created[] = [
                    'id' => (int) $frame->getId(),
                    'code' => $code,
                    'name' => $name,
                    'path' => $frame->getRealFullPath(),
                ];
                $createdFrames[] = $frame;
            } catch (\Throwable $e) {
                $errors[] = sprintf(
                    'Failed to create frame "%s" at step "%s": %s [%s:%d]',
                    $code,
                    $step ?? 'unknown',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                );
            }
        }

        if ($createdFrames !== []) {
            try {
                $this->addFramesToFinalProducts($model, $createdFrames, $submittedFinalProducts, $user);
            } catch (\Throwable $e) {
                $errors[] = sprintf('Failed to add generated frames to model finalProducts: %s', $e->getMessage());
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param list<Frame> $frames
     * @param list<array<string, mixed>>|null $submittedFinalProducts
     */
    private function addFramesToFinalProducts(ModelObject $model, array $frames, ?array $submittedFinalProducts, ?User $user): void
    {
        $finalProducts = $this->resolveFinalProducts($model, $submittedFinalProducts);
        $knownIds = [];

        foreach ($finalProducts as $finalProduct) {
            $knownIds[(int) $finalProduct->getId()] = true;
        }

        $added = false;
        foreach ($frames as $frame) {
            $frameId = (int) $frame->getId();
            if ($frameId < 1 || isset($knownIds[$frameId])) {
                continue;
            }

            $finalProducts[] = $frame;
            $knownIds[$frameId] = true;
            $added = true;
        }

        if (!$added) {
            return;
        }

        $model->setFinalProducts($finalProducts);

        if ($user instanceof User) {
            $model->setUserModification($user->getId());
        }

        $model->save();
    }

    /**
     * @param list<array<string, mixed>>|null $submittedFinalProducts
     *
     * @return list<Frame>
     */
    private function resolveFinalProducts(ModelObject $model, ?array $submittedFinalProducts): array
    {
        if ($submittedFinalProducts !== null) {
            return $this->resolveSubmittedFinalProducts($submittedFinalProducts);
        }

        $finalProducts = $model->getFinalProducts();
        if (!is_array($finalProducts)) {
            return [];
        }

        return $this->uniqueFrames($finalProducts);
    }

    /**
     * @param list<array<string, mixed>> $submittedFinalProducts
     *
     * @return list<Frame>
     */
    private function resolveSubmittedFinalProducts(array $submittedFinalProducts): array
    {
        $frames = [];

        foreach ($submittedFinalProducts as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : null;
            if (!is_scalar($id) || (int) $id < 1) {
                continue;
            }

            $frame = Frame::getById((int) $id, ['force' => true]);
            if ($frame instanceof Frame) {
                $frames[] = $frame;
            }
        }

        return $this->uniqueFrames($frames);
    }

    /**
     * @param array<array-key, mixed> $frames
     *
     * @return list<Frame>
     */
    private function uniqueFrames(array $frames): array
    {
        $unique = [];
        $seen = [];

        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                continue;
            }

            $frameId = (int) $frame->getId();
            if ($frameId < 1 || isset($seen[$frameId])) {
                continue;
            }

            $unique[] = $frame;
            $seen[$frameId] = true;
        }

        return $unique;
    }

    /**
     * @param list<array<string, mixed>>|null $submittedDetails
     *
     * @return list<array{mainColorCode: string, supplier: Supplier|null, composedColors: list<ObjectMetadata>, components: list<ObjectMetadata>}>
     */
    private function resolveDetails(ModelObject $model, ?array $submittedDetails): array
    {
        if ($submittedDetails !== null) {
            return $this->resolveSubmittedDetails($submittedDetails);
        }

        $collection = $model->getFinalProductDetails();
        if (!$collection instanceof Fieldcollection) {
            return [];
        }

        $details = [];
        foreach ($collection as $item) {
            if (!$item || $item->getType() !== 'finalProductProcess') {
                continue;
            }

            $supplier = $item->getSupplier();
            $details[] = [
                'mainColorCode' => $this->getFieldString($item, 'mainColorCode'),
                'supplier' => $supplier instanceof Supplier ? $supplier : null,
                'composedColors' => $this->resolveComposedColorsMetadata(
                    $this->getFieldValue($item, 'colors'),
                    $this->getFieldValue($item, 'composingColors'),
                    false
                ),
                'components' => $this->buildRelationMetadataFromSource(
                    $this->getFieldValue($item, 'components'),
                    'components',
                    []
                ),
            ];
        }

        return $details;
    }

    /**
     * @param list<array<string, mixed>> $submittedDetails
     *
     * @return list<array{mainColorCode: string, supplier: Supplier|null, composedColors: list<ObjectMetadata>, components: list<ObjectMetadata>}>
     */
    private function resolveSubmittedDetails(array $submittedDetails): array
    {
        $details = [];

        foreach ($submittedDetails as $item) {
            if (($item['type'] ?? null) !== 'finalProductProcess') {
                continue;
            }

            $data = is_array($item['data'] ?? null) ? $item['data'] : [];

            $details[] = [
                'mainColorCode' => $this->normalizeString($data['mainColorCode'] ?? null),
                'supplier' => $this->resolveSupplier($data['supplier'] ?? null),
                'composedColors' => $this->resolveComposedColorsMetadata(
                    $data['colors'] ?? [],
                    $data['composingColors'] ?? null,
                    true
                ),
                'components' => $this->buildRelationMetadataFromSource(
                    $data['components'] ?? null,
                    'components',
                    []
                ),
            ];
        }

        return $details;
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
            if (is_scalar($id) && trim((string) $id) !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids, SORT_REGULAR));
    }

    private function resolveSupplier(mixed $value): ?Supplier
    {
        $id = is_array($value) ? ($value['id'] ?? null) : $value;
        if (!is_scalar($id) || (int) $id < 1) {
            return null;
        }

        $supplier = Supplier::getById((int) $id, ['force' => true]);

        return $supplier instanceof Supplier ? $supplier : null;
    }

    private function findExistingChildFrame(ModelObject $model, string $code): ?Frame
    {
        $listing = new FrameListing();
        $listing->setUnpublished(true);
        $listing->setLimit(1);
        $listing->setCondition('parentId = ? AND code = ?', [$model->getId(), $code]);

        $frames = $listing->load();

        return $frames[0] ?? null;
    }

    /**
     * @param array{frameBaseCode?: mixed, name?: mixed}|null $submittedModelBaseData
     */
    private function resolveModelBaseFrameCode(ModelObject $model, ?array $submittedModelBaseData): string
    {
        $submittedValue = $this->normalizeString($submittedModelBaseData['frameBaseCode'] ?? null);

        return $submittedValue !== '' ? $submittedValue : $this->getFieldString($model, 'frameBaseCode');
    }

    /**
     * @param array{frameBaseCode?: mixed, name?: mixed}|null $submittedModelBaseData
     */
    private function resolveModelBaseName(ModelObject $model, ?array $submittedModelBaseData): string
    {
        $submittedValue = $this->normalizeString($submittedModelBaseData['name'] ?? null);

        return $submittedValue !== '' ? $submittedValue : $this->getFieldString($model, 'name');
    }

    /**
     * @return list<ObjectMetadata>
     */
    private function resolveComposedColorsMetadata(
        mixed $colorsValue,
        mixed $composingColorsValue,
        bool $preferColors
    ): array
    {
        if ($preferColors) {
            $colorIds = $this->normalizeColorIds($colorsValue);
            if ($colorIds !== []) {
                return $this->buildColorMetadataFromIds($colorIds);
            }

            return $this->buildColorMetadataFromSource($composingColorsValue);
        }

        $metadata = $this->buildColorMetadataFromSource($composingColorsValue);
        if ($metadata !== []) {
            return $metadata;
        }

        return $this->buildColorMetadataFromIds($this->normalizeColorIds($colorsValue));
    }

    /**
     * @param list<int|string> $colorIds
     *
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromIds(array $colorIds): array
    {
        return $this->buildColorMetadataFromObjects($this->resolveExpandedColorsFromIds($colorIds));
    }

    /**
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromSource(mixed $value): array
    {
        return $this->buildColorMetadataFromObjects(
            $this->resolveObjectsFromSource(
                $value,
                static fn (Concrete $object): bool => $object instanceof Color
            )
        );
    }

    /**
     * @param list<int|string> $colorIds
     *
     * @return list<Color>
     */
    private function resolveExpandedColorsFromIds(array $colorIds): array
    {
        $colors = [];
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

                $colors[] = $expandedColor;
                $seen[$expandedColorId] = true;
            }
        }

        return $colors;
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

    /**
     * @param list<Concrete> $colors
     *
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromObjects(array $colors): array
    {
        $metadata = [];
        $seen = [];

        foreach ($colors as $color) {
            if (!$color instanceof Color) {
                continue;
            }

            $colorId = (int) $color->getId();
            if ($colorId < 1 || isset($seen[$colorId])) {
                continue;
            }

            $item = new ObjectMetadata('composedColors', ['name', 'relevant'], $color);
            $item->setName($this->normalizeString($color->getName()));
            $item->setRelevant(true);
            $metadata[] = $item;
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
     * @param list<ObjectMetadata> $composedColors
     */
    private function buildFrameName(string $baseName, array $composedColors, string $mainColorCode): string
    {
        $colorCodes = [];
        foreach ($composedColors as $metadata) {
            $composedColor = $metadata->getObject();
            if (!$composedColor instanceof Color) {
                continue;
            }

            $code = $this->normalizeString($composedColor->getCode());
            if ($code !== '') {
                $colorCodes[] = $code;
            }
        }

        if ($colorCodes === [] && $mainColorCode !== '') {
            $colorCodes[] = $mainColorCode;
        }

        return $this->joinNonEmpty([$baseName, implode(' + ', $colorCodes)], ' ');
    }

    private function buildUniqueKey(ModelObject $model, string $code, string $name, int $detailIndex, string $mainColorCode): string
    {
        $key = ElementService::getValidKey($code, 'object');
        if ($key === '') {
            $key = ElementService::getValidKey($name, 'object');
        }
        if ($key === '') {
            $key = ElementService::getValidKey(
                sprintf('frame-%d-%s', $detailIndex + 1, $mainColorCode !== '' ? $mainColorCode : 'main'),
                'object'
            );
        }
        if ($key === '') {
            $key = sprintf('frame-%d', $detailIndex + 1);
        }

        $frame = new Frame();
        $frame->setParent($model);
        $frame->setKey($key);

        return ElementService::getUniqueKey($frame) ?? $key;
    }

    private function getFieldValue(object|null $object, string $fieldName): mixed
    {
        if ($object === null) {
            return null;
        }

        $getter = 'get' . ucfirst($fieldName);
        if (method_exists($object, $getter)) {
            try {
                return $object->$getter();
            } catch (\Throwable) {
            }
        }

        if (method_exists($object, 'getObjectVar')) {
            try {
                return $object->getObjectVar($fieldName);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function getFieldString(object|null $object, string $fieldName): string
    {
        return $this->normalizeString($this->getFieldValue($object, $fieldName));
    }

    private function setFieldValue(object $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);
        if (method_exists($object, $setter)) {
            try {
                $object->$setter($value);

                return;
            } catch (\Throwable) {
            }
        }

        if (method_exists($object, 'setObjectVar')) {
            try {
                $object->setObjectVar($fieldName, $value);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @param list<string> $parts
     */
    private function joinNonEmpty(array $parts, string $separator): string
    {
        $parts = array_values(array_filter(array_map($this->normalizeString(...), $parts), static fn (string $part): bool => $part !== ''));

        return implode($separator, $parts);
    }

    private function normalizeString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
