<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Model as ModelObject;
use Pimcore\Model\Element\Service as ElementService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FrameNameFromComposedColorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => ['onPreSave', -20],
            DataObjectEvents::PRE_UPDATE => ['onPreSave', -20],
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $frame = $event->getObject();
        if (!$frame instanceof Frame) {
            return;
        }

        $colorChanged = (int) $frame->getId() < 1 || $frame->isFieldDirty('color');
        if ($colorChanged) {
            $this->refreshComposedColorsFromLinkedColor($frame);
        }

        $colorCodes = $this->resolveColorCodes($frame);
        if ($colorCodes === []) {
            return;
        }

        $primaryColorCode = $this->resolvePrimaryColorCode($frame);
        $baseData = $this->resolveBaseData($frame, $colorCodes, $primaryColorCode);
        if ($baseData['name'] === '') {
            return;
        }

        $name = $this->joinNonEmpty([$baseData['name'], $this->formatColorCodes($colorCodes)], ' ');
        if ($name !== '' && $this->normalizeString($frame->getName()) !== $name) {
            $frame->setName($name);
        }

        if ($colorChanged && $baseData['code'] !== '' && $primaryColorCode !== '') {
            $this->updateCodeAndKey($frame, $baseData['code'], $primaryColorCode, $name);
        }
    }

    /**
     * @return list<string>
     */
    private function resolveColorCodes(Frame $frame): array
    {
        $codes = [];
        foreach (($frame->getComposedColors(['unpublished' => true]) ?: []) as $metadata) {
            $color = $this->resolveMetadataColor($metadata);
            if (!$color instanceof Color) {
                continue;
            }

            $code = $this->normalizeString($color->getCode());
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        if ($codes !== []) {
            return $codes;
        }

        $color = $frame->getColor(['unpublished' => true]);
        if (!$color instanceof Color) {
            return [];
        }

        $code = $this->normalizeString($color->getCode());

        return $code === '' ? [] : [$code];
    }

    private function resolveMetadataColor(mixed $metadata): ?Color
    {
        if ($metadata instanceof ObjectMetadata) {
            $color = $metadata->getObject();

            return $color instanceof Color ? $color : null;
        }

        return $metadata instanceof Color ? $metadata : null;
    }

    /**
     * @param list<string> $colorCodes
     *
     * @return array{code: string, name: string}
     */
    private function resolveBaseData(Frame $frame, array $colorCodes, string $primaryColorCode): array
    {
        $currentBaseData = [
            'code' => $this->stripTrailingColorCodes($this->normalizeString($frame->getCode()), [$primaryColorCode]),
            'name' => $this->stripTrailingColorCodes($this->normalizeString($frame->getName()), $colorCodes),
        ];

        if ((int) $frame->getId() < 1 && $currentBaseData['code'] !== '' && $currentBaseData['name'] !== '') {
            return $currentBaseData;
        }

        $baseData = $this->resolveBaseDataFromFinalProductDetails($frame);
        if ($baseData !== null) {
            return $baseData;
        }

        return $currentBaseData;
    }

    /**
     * @return array{code: string, name: string}|null
     */
    private function resolveBaseDataFromFinalProductDetails(Frame $frame): ?array
    {
        $model = $this->resolveModel($frame);
        if (!$model instanceof ModelObject) {
            return null;
        }

        $collection = $model->getFinalProductDetails();
        if (!$collection instanceof Fieldcollection) {
            return null;
        }

        $frameCode = $this->normalizeString($frame->getCode());
        $matchedData = null;
        $matchedCodeLength = -1;

        foreach ($collection as $item) {
            if (!$item || $item->getType() !== 'finalProductProcess') {
                continue;
            }

            $detailCode = $this->normalizeString($item->getCode());
            if (
                $detailCode === ''
                || $this->isFrameCodeForDetailCode($frameCode, $detailCode) === false
                || strlen($detailCode) <= $matchedCodeLength
            ) {
                continue;
            }

            $matchedData = [
                'code' => $detailCode,
                'name' => $this->normalizeString($item->getName()),
            ];
            $matchedCodeLength = strlen($detailCode);
        }

        return $matchedData;
    }

    private function resolveModel(Frame $frame): ?ModelObject
    {
        $parent = $frame->getParent();
        if ($parent instanceof ModelObject) {
            return $parent;
        }

        $artBase = $frame->getArtBase();

        return $artBase instanceof ModelObject ? $artBase : null;
    }

    private function isFrameCodeForDetailCode(string $frameCode, string $detailCode): bool
    {
        return $frameCode === $detailCode || str_starts_with($frameCode, $detailCode . ' ');
    }

    private function refreshComposedColorsFromLinkedColor(Frame $frame): void
    {
        $color = $frame->getColor(['unpublished' => true]);
        if (!$color instanceof Color) {
            $frame->setComposedColors([]);

            return;
        }

        $frame->setComposedColors($this->buildComposedColorsMetadata($color));
    }

    /**
     * @return list<ObjectMetadata>
     */
    private function buildComposedColorsMetadata(Color $color): array
    {
        $metadata = [];

        foreach (($color->getMultiColor(['unpublished' => true]) ?: []) as $composedColor) {
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

    private function resolvePrimaryColorCode(Frame $frame): string
    {
        $color = $frame->getColor(['unpublished' => true]);

        return $color instanceof Color ? $this->normalizeString($color->getCode()) : '';
    }

    private function updateCodeAndKey(Frame $frame, string $baseCode, string $primaryColorCode, string $name): void
    {
        $code = $this->joinNonEmpty([$baseCode, $primaryColorCode], ' ');
        if ($code === '') {
            return;
        }

        if ($this->normalizeString($frame->getCode()) !== $code) {
            $frame->setCode($code);
        }

        $key = $this->buildUniqueKey($frame, $code, $name);
        if ($key !== '' && $this->normalizeString($frame->getKey()) !== $key) {
            $frame->setKey($key);
        }
    }

    private function buildUniqueKey(Frame $frame, string $code, string $name): string
    {
        $key = ElementService::getValidKey($code, 'object');
        if ($key === '') {
            $key = ElementService::getValidKey($name, 'object');
        }
        if ($key === '') {
            return '';
        }

        $frame->setKey($key);

        try {
            return ElementService::getUniqueKey($frame) ?? $key;
        } catch (\Throwable) {
            return $key;
        }
    }

    /**
     * @param list<string> $colorCodes
     */
    private function stripTrailingColorCodes(string $name, array $colorCodes): string
    {
        $base = $name;
        $codesByLength = $colorCodes;
        usort($codesByLength, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        for ($i = 0, $maxRemovals = count($colorCodes); $i < $maxRemovals; $i++) {
            foreach ($codesByLength as $code) {
                if ($code === '') {
                    continue;
                }

                $nextBase = preg_replace('/(?:\s*\+\s*|\s+)' . preg_quote($code, '/') . '$/u', '', $base, 1, $replacements);
                if ($replacements < 1 || $nextBase === null) {
                    continue;
                }

                $base = rtrim($nextBase);

                continue 2;
            }

            break;
        }

        return $base;
    }

    /**
     * @param list<string> $colorCodes
     */
    private function formatColorCodes(array $colorCodes): string
    {
        return implode(' + ', $colorCodes);
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
