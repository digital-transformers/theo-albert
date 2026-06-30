<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Color\Listing as ColorListing;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\FinalProductProcess;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Model as ModelObject;
use Pimcore\Model\DataObject\SAPItemGroup;
use Pimcore\Model\DataObject\SAPItemGroup\Listing as SAPItemGroupListing;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Element\Service as ElementService;
use RuntimeException;

final class ProductHierarchySyncService
{
    private const PAGE_SIZE = 500;
    private const RUNTIME_CLEANUP_INTERVAL = 100;

    public function __construct(private readonly ProductHierarchyGraphqlClient $client)
    {
    }

    /**
     * @param array{
     *     families: list<array<string, mixed>>,
     *     models: list<array<string, mixed>>,
     *     frames: list<array<string, mixed>>,
     *     report: array<string, mixed>
     * } $converted
     * @param callable(array<string, mixed>): void|null $progress
     *
     * @return array<string, mixed>
     */
    public function sync(array $converted, ?callable $progress = null): array
    {
        $result = [
            'families' => ['created' => 0, 'updated' => 0, 'failed' => 0],
            'models' => ['created' => 0, 'updated' => 0, 'failed' => 0],
            'frames' => ['created' => 0, 'updated' => 0, 'failed' => 0],
            'errors' => [],
        ];

        $familyIndex = $this->loadIndex('Family');
        $modelIndex = $this->loadIndex('Model');
        $frameIndex = $this->loadIndex('Frame');

        $this->syncFamilies($converted['families'], $familyIndex, $result, $progress);
        $this->syncModels($converted['models'], $familyIndex, $modelIndex, $result, $progress);
        $this->syncFrames($converted['frames'], $modelIndex, $frameIndex, $result, $progress);
        $this->enrichModels($converted['models'], $converted['frames'], $modelIndex, $frameIndex, $result, $progress);

        return $result;
    }

    /**
     * @return array<string, array{id: int, fullpath: string}>
     */
    private function loadIndex(string $entity): array
    {
        $index = [];
        $offset = 0;
        $field = 'get' . $entity . 'Listing';

        do {
            $query = sprintf(
                'query Existing($first: Int!, $after: Int!) {
                    %s(first: $first, after: $after, published: false) {
                        edges { node { id fullpath code } }
                    }
                }',
                $field
            );
            $data = $this->client->execute($query, ['first' => self::PAGE_SIZE, 'after' => $offset]);
            $edges = $data[$field]['edges'] ?? [];
            if (!is_array($edges)) {
                throw new RuntimeException(sprintf('Invalid %s listing response.', $entity));
            }

            foreach ($edges as $edge) {
                $node = is_array($edge) && is_array($edge['node'] ?? null) ? $edge['node'] : [];
                $code = $this->stringValue($node['code'] ?? null);
                if ($code !== '' && !isset($index[$code])) {
                    $index[$code] = [
                        'id' => (int) ($node['id'] ?? 0),
                        'fullpath' => $this->stringValue($node['fullpath'] ?? null),
                    ];
                }
            }

            $count = count($edges);
            $offset += $count;
        } while ($count === self::PAGE_SIZE);

        return $index;
    }

    /**
     * @param list<array<string, mixed>> $families
     * @param array<string, array{id: int, fullpath: string}> $familyIndex
     * @param array<string, mixed> $result
     * @param callable(array<string, mixed>): void|null $progress
     */
    private function syncFamilies(array $families, array &$familyIndex, array &$result, ?callable $progress): void
    {
        $total = count($families);
        foreach ($families as $position => $family) {
            $code = $this->stringValue($family['family_code'] ?? null);
            $input = [
                'code' => $code,
                'name' => $this->objectSafeString($family['family_name'] ?? null) ?: $code,
                'published' => true,
            ];

            try {
                if (isset($familyIndex[$code])) {
                    $output = $this->mutate('updateFamily', ['id' => $familyIndex[$code]['id'], 'input' => $input]);
                    ++$result['families']['updated'];
                } else {
                    $output = $this->mutate('createFamily', [
                        'path' => $this->stringValue($family['import_parent_path'] ?? null),
                        'key' => $code,
                        'published' => true,
                        'input' => $input,
                    ]);
                    ++$result['families']['created'];
                }
                $familyIndex[$code] = $output;
            } catch (\Throwable $exception) {
                ++$result['families']['failed'];
                $result['errors'][] = $this->error('family', $code, $exception);
            }

            $this->reportProgress($progress, 'families', $position + 1, $total, $result);
        }
    }

    /**
     * @param list<array<string, mixed>> $models
     * @param array<string, array{id: int, fullpath: string}> $familyIndex
     * @param array<string, array{id: int, fullpath: string}> $modelIndex
     * @param array<string, mixed> $result
     * @param callable(array<string, mixed>): void|null $progress
     */
    private function syncModels(
        array $models,
        array $familyIndex,
        array &$modelIndex,
        array &$result,
        ?callable $progress,
    ): void {
        $total = count($models);
        foreach ($models as $position => $model) {
            $code = $this->stringValue($model['model_code'] ?? null);
            $familyCode = $this->stringValue($model['parent_family_code'] ?? null);
            $parent = $familyIndex[$familyCode] ?? null;
            if ($parent === null) {
                ++$result['models']['failed'];
                $result['errors'][] = ['entity' => 'model', 'code' => $code, 'message' => 'Parent family is unavailable.'];
                $this->reportProgress($progress, 'models', $position + 1, $total, $result);
                continue;
            }

            $input = array_filter([
                'code' => $code,
                'name' => $this->objectSafeString($model['model_name'] ?? null) ?: $code,
                'frameBaseCode' => $this->stringValue($model['frame_base_code'] ?? null),
                'seriesCode' => $this->stringValue($model['series_code'] ?? null),
                'material' => $this->stringValue($model['material'] ?? null),
                'description' => $this->nullableString($model['description'] ?? null),
                'published' => true,
            ], static fn (mixed $value): bool => $value !== null);

            try {
                if (isset($modelIndex[$code])) {
                    $output = $this->mutate('updateModel', [
                        'id' => $modelIndex[$code]['id'],
                        'parentId' => $parent['id'],
                        'input' => $input,
                    ]);
                    $status = 'updated';
                } else {
                    $output = $this->mutate('createModel', [
                        'parentId' => $parent['id'],
                        'key' => $code,
                        'published' => true,
                        'input' => $input,
                    ]);
                    $status = 'created';
                }
                $modelIndex[$code] = $output;
                ++$result['models'][$status];
            } catch (\Throwable $exception) {
                ++$result['models']['failed'];
                $result['errors'][] = $this->error('model', $code, $exception);
            }

            $this->reportProgress($progress, 'models', $position + 1, $total, $result);
        }
    }

    /**
     * @param list<array<string, mixed>> $frames
     * @param array<string, array{id: int, fullpath: string}> $modelIndex
     * @param array<string, array{id: int, fullpath: string}> $frameIndex
     * @param array<string, mixed> $result
     * @param callable(array<string, mixed>): void|null $progress
     */
    private function syncFrames(
        array $frames,
        array $modelIndex,
        array &$frameIndex,
        array &$result,
        ?callable $progress,
    ): void {
        $total = count($frames);
        foreach ($frames as $position => $frame) {
            $code = $this->stringValue($frame['frame_code'] ?? null);
            $modelCode = $this->stringValue($frame['parent_model_code'] ?? null);
            $parent = $modelIndex[$modelCode] ?? null;
            if ($parent === null) {
                ++$result['frames']['failed'];
                $result['errors'][] = ['entity' => 'frame', 'code' => $code, 'message' => 'Parent model is unavailable.'];
                $this->reportProgress($progress, 'frames', $position + 1, $total, $result);
                continue;
            }

            $input = [
                'code' => $code,
                'name' => $this->objectSafeString($frame['frame_name'] ?? null) ?: $code,
                'mainColorCode' => $this->stringValue($frame['main_color_code'] ?? null),
                'seriesCode' => $this->stringValue($frame['series_code'] ?? null),
                'ecomFileName' => $this->stringValue($frame['ecom_file_name'] ?? null),
                'exchangeCode' => $this->stringValue($frame['exchange_code'] ?? null),
                'artBase' => ['type' => 'object', 'fullpath' => $parent['fullpath']],
                'published' => true,
            ];

            try {
                $sourceProductCode = $this->stringValue($frame['source']['product_code'] ?? null);
                $existingCode = isset($frameIndex[$code])
                    ? $code
                    : ($sourceProductCode !== $code && isset($frameIndex[$sourceProductCode]) ? $sourceProductCode : null);
                if ($existingCode !== null) {
                    $output = $this->mutate('updateFrame', [
                        'id' => $frameIndex[$existingCode]['id'],
                        'parentId' => $parent['id'],
                        'input' => $input,
                    ]);
                    $status = 'updated';
                    if ($existingCode !== $code) {
                        unset($frameIndex[$existingCode]);
                    }
                } else {
                    $output = $this->mutate('createFrame', [
                        'parentId' => $parent['id'],
                        'key' => $code,
                        'published' => true,
                        'input' => $input,
                    ]);
                    $status = 'created';
                }
                $frameIndex[$code] = $output;
                $this->enrichFrame($output['id'], $frame);
                ++$result['frames'][$status];
            } catch (\Throwable $exception) {
                ++$result['frames']['failed'];
                $result['errors'][] = $this->error('frame', $code, $exception);
            }

            $this->reportProgress($progress, 'frames', $position + 1, $total, $result);
            $this->cleanUpRuntimeCache($position + 1);
        }
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array{id: int, fullpath: string}
     */
    private function mutate(string $mutation, array $variables): array
    {
        $arguments = [];
        $definitions = [];
        foreach ($variables as $name => $value) {
            $type = match ($name) {
                'id', 'parentId' => 'Int',
                'published' => 'Boolean',
                'input' => match ($mutation) {
                    'createFamily', 'updateFamily' => 'UpdateFamilyInput',
                    'createModel', 'updateModel' => 'UpdateModelInput',
                    default => 'UpdateFrameInput',
                },
                default => 'String',
            };
            $definitions[] = '$' . $name . ': ' . $type . ($name === 'key' ? '!' : '');
            $arguments[] = $name . ': $' . $name;
        }

        $query = sprintf(
            'mutation Sync(%s) {
                %s(%s) {
                    success
                    message
                    output { id fullpath }
                }
            }',
            implode(', ', $definitions),
            $mutation,
            implode(', ', $arguments)
        );
        $data = $this->client->execute($query, $variables);
        $payload = is_array($data[$mutation] ?? null) ? $data[$mutation] : [];
        if (($payload['success'] ?? false) !== true || !is_array($payload['output'] ?? null)) {
            throw new RuntimeException((string) ($payload['message'] ?? $mutation . ' failed.'));
        }

        return [
            'id' => (int) ($payload['output']['id'] ?? 0),
            'fullpath' => $this->stringValue($payload['output']['fullpath'] ?? null),
        ];
    }

    /**
     * @param callable(array<string, mixed>): void|null $progress
     * @param array<string, mixed> $result
     */
    private function reportProgress(?callable $progress, string $stage, int $current, int $total, array $result): void
    {
        if ($progress !== null && ($current === $total || $current % 25 === 0)) {
            $progress([
                'stage' => $stage,
                'current' => $current,
                'total' => $total,
                'result' => [
                    'families' => $result['families'],
                    'models' => $result['models'],
                    'frames' => $result['frames'],
                    'error_count' => count($result['errors']),
                ],
            ]);
        }
    }

    /**
     * @return array{entity: string, code: string, message: string}
     */
    private function error(string $entity, string $code, \Throwable $exception): array
    {
        return ['entity' => $entity, 'code' => $code, 'message' => $exception->getMessage()];
    }

    /**
     * @param list<array<string, mixed>> $models
     * @param list<array<string, mixed>> $frames
     * @param array<string, array{id: int, fullpath: string}> $modelIndex
     * @param array<string, array{id: int, fullpath: string}> $frameIndex
     * @param array<string, mixed> $result
     * @param callable(array<string, mixed>): void|null $progress
     */
    private function enrichModels(
        array $models,
        array $frames,
        array $modelIndex,
        array $frameIndex,
        array &$result,
        ?callable $progress,
    ): void {
        $framesByModel = [];
        foreach ($frames as $frame) {
            $modelCode = $this->stringValue($frame['parent_model_code'] ?? null);
            if ($modelCode !== '') {
                $framesByModel[$modelCode][] = $frame;
            }
        }

        $total = count($models);
        foreach ($models as $position => $model) {
            $code = $this->stringValue($model['model_code'] ?? null);
            $id = (int) ($modelIndex[$code]['id'] ?? 0);
            if ($code === '' || $id < 1) {
                $this->reportProgress($progress, 'model relations', $position + 1, $total, $result);
                continue;
            }

            try {
                $this->enrichModel($id, $model, $framesByModel[$code] ?? [], $frameIndex);
            } catch (\Throwable $exception) {
                $result['errors'][] = $this->error('model_enrichment', $code, $exception);
            }

            $this->reportProgress($progress, 'model relations', $position + 1, $total, $result);
            $this->cleanUpRuntimeCache($position + 1);
        }
    }

    private function cleanUpRuntimeCache(int $position): void
    {
        if ($position % self::RUNTIME_CLEANUP_INTERVAL !== 0 || !\Pimcore::hasContainer()) {
            return;
        }

        RuntimeCache::clear();
        gc_collect_cycles();
    }

    /**
     * @param array<string, mixed> $model
     * @param list<array<string, mixed>> $frames
     * @param array<string, array{id: int, fullpath: string}> $frameIndex
     */
    private function enrichModel(int $id, array $model, array $frames, array $frameIndex): void
    {
        $details = is_array($model['final_product_details'] ?? null) ? $model['final_product_details'] : [];
        $finalProducts = $this->resolveImportedFrames($frames, $frameIndex);

        $object = ModelObject::getById($id, ['force' => true]);
        if (!$object instanceof ModelObject) {
            return;
        }

        $changed = false;
        if ($details !== []) {
            $items = [];
            foreach ($details as $detail) {
                if (!is_array($detail)) {
                    continue;
                }

                $mainColorCode = $this->stringValue($detail['main_color_code'] ?? null);
                $colorIds = $this->resolveColorIds($detail['color_codes'] ?? []);
                if ($mainColorCode === '') {
                    continue;
                }

                $item = new FinalProductProcess();
                $item->setMainColorCode($mainColorCode);
                $item->setColors($colorIds);
                $items[] = $item;
            }

            if ($items !== []) {
                $object->setFinalProductDetails(new Fieldcollection($items, 'finalProductDetails'));
                $changed = true;
            }
        }

        if ($finalProducts !== []) {
            $object->setFinalProducts($this->mergeFinalProducts($object, $finalProducts));
            $changed = true;
        }

        if (!$changed) {
            return;
        }

        $object->save();
    }

    /**
     * @param list<array<string, mixed>> $frames
     * @param array<string, array{id: int, fullpath: string}> $frameIndex
     *
     * @return list<Frame>
     */
    private function resolveImportedFrames(array $frames, array $frameIndex): array
    {
        $resolved = [];
        $seen = [];
        foreach ($frames as $frame) {
            $code = $this->stringValue($frame['frame_code'] ?? null);
            $id = (int) ($frameIndex[$code]['id'] ?? 0);
            if ($code === '' || $id < 1 || isset($seen[$id])) {
                continue;
            }

            $object = Frame::getById($id, ['force' => true]);
            if (!$object instanceof Frame) {
                continue;
            }

            $resolved[] = $object;
            $seen[$id] = true;
        }

        return $resolved;
    }

    /**
     * @param list<Frame> $importedFrames
     *
     * @return list<Frame>
     */
    private function mergeFinalProducts(ModelObject $model, array $importedFrames): array
    {
        $merged = [];
        $seen = [];
        $currentFrames = $model->getFinalProducts();

        foreach (is_array($currentFrames) ? $currentFrames : [] as $frame) {
            if (!$frame instanceof Frame) {
                continue;
            }

            $id = (int) $frame->getId();
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }

            $merged[] = $frame;
            $seen[$id] = true;
        }

        foreach ($importedFrames as $frame) {
            $id = (int) $frame->getId();
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }

            $merged[] = $frame;
            $seen[$id] = true;
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $frame
     */
    private function enrichFrame(int $id, array $frame): void
    {
        $hasItemGroups = $this->nonEmptyStrings($frame['item_group_numbers'] ?? []) !== [];
        $hasColors = array_key_exists('composed_color_codes', $frame);
        $hasDsArtCat = array_key_exists('category_code', $frame);
        $hasDsType = array_key_exists('line_code', $frame);
        if (!$hasItemGroups && !$hasColors && !$hasDsArtCat && !$hasDsType) {
            return;
        }

        $object = Frame::getById($id, ['force' => true]);
        if (!$object instanceof Frame) {
            return;
        }

        $expectedKey = ElementService::getValidKey($this->stringValue($frame['frame_code'] ?? null), 'object');
        if ($expectedKey !== '' && $object->getKey() !== $expectedKey) {
            $object->setKey($expectedKey);
        }

        if ($hasItemGroups) {
            $object->setItemGroup($this->resolveItemGroups($frame['item_group_numbers'] ?? []));
        }

        if ($hasColors) {
            $object->setComposedColors($this->resolveComposedColors($frame['composed_color_codes'] ?? []));
        }

        if ($hasDsArtCat) {
            $object->setDsArtCat($this->nullableString($frame['category_code'] ?? null));
        }

        if ($hasDsType) {
            $object->setDsType($this->nullableString($frame['line_code'] ?? null));
        }

        $object->save();
    }

    /**
     * @return list<string>
     */
    private function resolveColorIds(mixed $codes): array
    {
        $ids = [];
        foreach ($this->nonEmptyStrings($codes) as $code) {
            $color = $this->findColorByCode($code);
            if (!$color instanceof Color) {
                continue;
            }

            $id = (string) $color->getId();
            if ($id !== '' && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return list<ObjectMetadata>
     */
    private function resolveComposedColors(mixed $codes): array
    {
        $metadata = [];
        $seen = [];
        foreach ($this->nonEmptyStrings($codes) as $code) {
            $color = $this->findColorByCode($code);
            if (!$color instanceof Color) {
                continue;
            }

            $id = (int) $color->getId();
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }

            $item = new ObjectMetadata('composedColors', ['name', 'relevant'], $color);
            $item->setName($this->stringValue($color->getName()));
            $item->setRelevant(true);
            $metadata[] = $item;
            $seen[$id] = true;
        }

        return $metadata;
    }

    /**
     * @return list<SAPItemGroup>
     */
    private function resolveItemGroups(mixed $groupNumbers): array
    {
        $groups = [];
        $seen = [];
        foreach ($this->nonEmptyStrings($groupNumbers) as $groupNumber) {
            $group = $this->findSAPItemGroupByNumber($groupNumber);
            if (!$group instanceof SAPItemGroup) {
                continue;
            }

            $id = (int) $group->getId();
            if ($id < 1 || isset($seen[$id])) {
                continue;
            }

            $groups[] = $group;
            $seen[$id] = true;
        }

        return $groups;
    }

    private function findColorByCode(string $code): ?Color
    {
        $listing = new ColorListing();
        $listing->setUnpublished(true);
        $listing->setLimit(1);
        $listing->setCondition('code = ?', [$code]);
        $items = $listing->load();
        $color = $items[0] ?? null;

        return $color instanceof Color ? $color : null;
    }

    private function findSAPItemGroupByNumber(string $groupNumber): ?SAPItemGroup
    {
        $listing = new SAPItemGroupListing();
        $listing->setUnpublished(true);
        $listing->setLimit(1);
        $listing->setCondition('groupNum = ?', [$groupNumber]);
        $items = $listing->load();
        $group = $items[0] ?? null;

        return $group instanceof SAPItemGroup ? $group : null;
    }

    /**
     * @return list<string>
     */
    private function nonEmptyStrings(mixed $value): array
    {
        if (is_string($value)) {
            $value = $value === '' ? [] : [$value];
        }
        if (!is_array($value)) {
            return [];
        }

        $strings = [];
        foreach ($value as $item) {
            $string = $this->stringValue($item);
            if ($string !== '' && !in_array($string, $strings, true)) {
                $strings[] = $string;
            }
        }

        return $strings;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = $this->stringValue($value);

        return $value === '' ? null : $value;
    }

    private function objectSafeString(mixed $value): string
    {
        return trim(str_replace(['/', '\\'], '-', $this->stringValue($value)));
    }
}
