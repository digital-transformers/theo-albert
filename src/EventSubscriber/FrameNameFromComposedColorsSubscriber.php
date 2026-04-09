<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
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

        $mainColorCode = $this->getFieldString($frame, 'mainColorCode');
        $colorCodes = $this->resolveColorCodes($frame, $mainColorCode);
        $baseFrameCode = $this->resolveBaseFrameCode($frame, $mainColorCode);
        $baseName = $this->resolveBaseName($frame, $colorCodes);

        $currentCode = $this->normalizeString($frame->getCode());
        $rebuiltCode = $this->joinNonEmpty([$baseFrameCode, $mainColorCode], ' ');
        $code = $rebuiltCode !== '' ? $rebuiltCode : $currentCode;
        if ($rebuiltCode !== '' && $currentCode !== $rebuiltCode) {
            $frame->setCode($rebuiltCode);
        }

        $currentName = $this->normalizeString($frame->getName());
        $rebuiltName = $this->joinNonEmpty([$baseName, $this->formatColorCodes($colorCodes)], ' ');
        $name = $rebuiltName !== '' ? $rebuiltName : $currentName;
        if ($rebuiltName !== '' && $currentName !== $rebuiltName) {
            $frame->setName($rebuiltName);
        }

        $key = $this->buildUniqueKey($frame, $code, $name);
        if ($key !== '' && $this->normalizeString($frame->getKey()) !== $key) {
            $frame->setKey($key);
        }
    }

    /**
     * @return list<string>
     */
    private function resolveColorCodes(Frame $frame, string $mainColorCode): array
    {
        $codes = [];
        $seen = [];

        foreach (($frame->getComposedColors(['unpublished' => true]) ?: []) as $metadata) {
            $color = $this->resolveMetadataColor($metadata);
            if (!$color instanceof Color) {
                continue;
            }

            $code = $this->normalizeString($color->getCode());
            if ($code === '' || isset($seen[$code])) {
                continue;
            }

            $codes[] = $code;
            $seen[$code] = true;
        }

        if ($codes !== []) {
            return $codes;
        }

        return $mainColorCode !== '' ? [$mainColorCode] : [];
    }

    private function resolveMetadataColor(mixed $metadata): ?Color
    {
        if ($metadata instanceof ObjectMetadata) {
            $color = $metadata->getObject();

            return $color instanceof Color ? $color : null;
        }

        return $metadata instanceof Color ? $metadata : null;
    }

    private function resolveBaseFrameCode(Frame $frame, string $mainColorCode): string
    {
        $model = $this->resolveModel($frame);
        if ($model instanceof ModelObject) {
            $modelBaseFrameCode = $this->getFieldString($model, 'frameBaseCode');
            if ($modelBaseFrameCode !== '') {
                return $modelBaseFrameCode;
            }
        }

        $currentCode = $this->normalizeString($frame->getCode());
        if ($currentCode === '') {
            return '';
        }

        if ($mainColorCode === '') {
            return $currentCode;
        }

        return $this->stripTrailingColorCodes($currentCode, [$mainColorCode]);
    }

    /**
     * @param list<string> $colorCodes
     */
    private function resolveBaseName(Frame $frame, array $colorCodes): string
    {
        $model = $this->resolveModel($frame);
        if ($model instanceof ModelObject) {
            $modelName = $this->getFieldString($model, 'name');
            if ($modelName !== '') {
                return $modelName;
            }
        }

        return $this->stripTrailingColorCodes($this->normalizeString($frame->getName()), $colorCodes);
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
            $object->$setter($value);

            return;
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
