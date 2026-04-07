<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\Color;
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
     *
     * @return array{created: list<array{id: int, code: string, name: string, path: string}>, skipped: list<array{code: string, reason: string}>, errors: list<string>}
     */
    public function generate(
        ModelObject $model,
        ?array $submittedDetails = null,
        ?User $user = null,
        ?array $submittedFinalProducts = null,
    ): array {
        $created = [];
        $createdFrames = [];
        $skipped = [];
        $errors = [];

        foreach ($this->resolveDetails($model, $submittedDetails) as $index => $detail) {
            $baseCode = $this->normalizeString($detail['code'] ?? null);
            $baseName = $this->normalizeString($detail['name'] ?? null);
            $supplier = $detail['supplier'] ?? null;

            if ($detail['colorIds'] === []) {
                $skipped[] = [
                    'code' => $baseCode,
                    'reason' => sprintf('Fieldcollection item %d has no selected colors', $index + 1),
                ];
                continue;
            }

            foreach ($detail['colorIds'] as $colorId) {
                $color = Color::getById((int) $colorId, ['force' => true]);
                if (!$color instanceof Color) {
                    $skipped[] = [
                        'code' => $this->joinNonEmpty([$baseCode, (string) $colorId], ' '),
                        'reason' => sprintf('Color object %s was not found', (string) $colorId),
                    ];
                    continue;
                }

                $code = $this->joinNonEmpty([$baseCode, $this->normalizeString($color->getCode())], ' ');
                $composedColors = $this->buildComposedColorsMetadata($color);
                $name = $this->buildFrameName($baseName, $color, $composedColors);

                if ($code === '') {
                    $errors[] = sprintf('Fieldcollection item %d with color %d has no generated frame code', $index + 1, $color->getId());
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
                    $frame = new Frame();
                    $frame->setParent($model);
                    $frame->setKey($this->buildUniqueKey($model, $code, $name, $index, $color));
                    $frame->setPublished(false);
                    $frame->setCode($code);
                    $frame->setName($name);
                    $frame->setSupplier($supplier instanceof Supplier ? $supplier : null);
                    $frame->setColor($color);

                    if (method_exists($frame, 'setArtBase')) {
                        $frame->setArtBase($model);
                    }

                    if ($composedColors !== []) {
                        $frame->setComposedColors($composedColors);
                    }

                    if ($user instanceof User) {
                        $frame->setUserOwner($user->getId());
                        $frame->setUserModification($user->getId());
                    }

                    $frame->save();

                    $created[] = [
                        'id' => (int) $frame->getId(),
                        'code' => $code,
                        'name' => $name,
                        'path' => $frame->getRealFullPath(),
                    ];
                    $createdFrames[] = $frame;
                } catch (\Throwable $e) {
                    $errors[] = sprintf('Failed to create frame "%s": %s', $code, $e->getMessage());
                }
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
     * @return list<array{code: string, name: string, supplier: Supplier|null, colorIds: list<int|string>}>
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
                'code' => $this->normalizeString($item->getCode()),
                'name' => $this->normalizeString($item->getName()),
                'supplier' => $supplier instanceof Supplier ? $supplier : null,
                'colorIds' => $this->normalizeColorIds($item->getColors() ?: []),
            ];
        }

        return $details;
    }

    /**
     * @param list<array<string, mixed>> $submittedDetails
     *
     * @return list<array{code: string, name: string, supplier: Supplier|null, colorIds: list<int|string>}>
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
                'code' => $this->normalizeString($data['code'] ?? null),
                'name' => $this->normalizeString($data['name'] ?? null),
                'supplier' => $this->resolveSupplier($data['supplier'] ?? null),
                'colorIds' => $this->normalizeColorIds($data['colors'] ?? []),
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
     * @return list<ObjectMetadata>
     */
    private function buildComposedColorsMetadata(Color $color): array
    {
        $multiColor = $color->getMultiColor(['unpublished' => true]) ?: [];
        $metadata = [];

        foreach ($multiColor as $composedColor) {
            if (!$composedColor instanceof Color) {
                continue;
            }

            $item = new ObjectMetadata('composedColors', ['name', 'relevant'], $composedColor);
            $item->setName($this->normalizeString($composedColor->getName()));
            $item->setRelevant(true);
            $metadata[] = $item;
        }

        return $metadata;
    }

    /**
     * @param list<ObjectMetadata> $composedColors
     */
    private function buildFrameName(string $baseName, Color $color, array $composedColors): string
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

        if ($colorCodes === []) {
            $colorCode = $this->normalizeString($color->getCode());
            if ($colorCode !== '') {
                $colorCodes[] = $colorCode;
            }
        }

        return $this->joinNonEmpty([$baseName, implode(' ', $colorCodes)], '  ');
    }

    private function buildUniqueKey(ModelObject $model, string $code, string $name, int $detailIndex, Color $color): string
    {
        $key = ElementService::getValidKey($code, 'object');
        if ($key === '') {
            $key = ElementService::getValidKey($name, 'object');
        }
        if ($key === '') {
            $key = sprintf('frame-%d-%d', $detailIndex + 1, $color->getId());
        }

        $frame = new Frame();
        $frame->setParent($model);
        $frame->setKey($key);

        return ElementService::getUniqueKey($frame) ?? $key;
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
