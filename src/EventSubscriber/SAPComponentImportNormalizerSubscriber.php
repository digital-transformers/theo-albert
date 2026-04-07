<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Model\DataObject\ClassDefinition\Data\ColorOptionsProvider;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Color\Listing as ColorListing;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SAPComponentImportNormalizerSubscriber implements EventSubscriberInterface
{
    /**
     * SAP sends "tens", while the Pimcore multiselect value is "lens".
     */
    private const TYPE_ALIASES = [
        'tens' => ['lens', 'tens'],
        'lenses' => ['lens'],
    ];

    public function __construct(private readonly ColorOptionsProvider $colorOptionsProvider)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $object = $event->getObject();

        if (
            !$object instanceof Concrete
            || ($object->getClassName() !== 'SAPComponent' && $object->getClassId() !== 'component')
        ) {
            return;
        }

        $this->setItemTypeFromImportType($object);
        $this->setLinkedColorSelectValue($object);
    }

    private function setItemTypeFromImportType(Concrete $object): void
    {
        $importType = $this->getFieldValue($object, 'importType');
        if (!is_scalar($importType) || trim((string) $importType) === '') {
            return;
        }

        $optionMap = $this->buildItemTypeOptionMap($object);
        if ($optionMap === []) {
            return;
        }

        $itemTypes = [];
        foreach ($this->splitImportList((string) $importType) as $rawType) {
            $itemType = $this->resolveItemTypeValue($rawType, $optionMap);
            if ($itemType !== null && !in_array($itemType, $itemTypes, true)) {
                $itemTypes[] = $itemType;
            }
        }

        if ($itemTypes !== []) {
            $this->setFieldValue($object, 'itemType', $itemTypes);
        }
    }

    private function setLinkedColorSelectValue(Concrete $object): void
    {
        $linkedColor = $this->getFieldValue($object, 'linkedColor');
        if (!is_scalar($linkedColor) || trim((string) $linkedColor) === '') {
            return;
        }

        $selectValue = $this->resolveLinkedColorSelectValue((string) $linkedColor, $object);
        if ($selectValue !== null) {
            $this->setFieldValue($object, 'linkedColor', $selectValue);
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildItemTypeOptionMap(Concrete $object): array
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('itemType');
        if ($fieldDefinition === null || !method_exists($fieldDefinition, 'getOptions')) {
            return [];
        }

        $optionMap = [];
        foreach ($fieldDefinition->getOptions() ?? [] as $option) {
            $value = trim((string) ($option['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $optionMap[$this->normalizeToken($value)] = $value;

            $label = trim((string) ($option['key'] ?? ''));
            if ($label !== '') {
                $optionMap[$this->normalizeToken($label)] = $value;
            }
        }

        return $optionMap;
    }

    /**
     * @param array<string, string> $optionMap
     */
    private function resolveItemTypeValue(string $rawType, array $optionMap): ?string
    {
        $normalizedType = $this->normalizeToken($rawType);
        if ($normalizedType === '') {
            return null;
        }

        if (isset($optionMap[$normalizedType])) {
            return $optionMap[$normalizedType];
        }

        foreach (self::TYPE_ALIASES[$normalizedType] ?? [] as $alias) {
            $normalizedAlias = $this->normalizeToken($alias);
            if (isset($optionMap[$normalizedAlias])) {
                return $optionMap[$normalizedAlias];
            }
        }

        return null;
    }

    private function resolveLinkedColorSelectValue(string $rawColor, Concrete $object): ?string
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('linkedColor');
        if ($fieldDefinition !== null) {
            $context = [
                'object' => $object,
                'class' => $object->getClass(),
                'fieldname' => 'linkedColor',
            ];

            $selectValue = $this->resolveLinkedColorFromOptions(
                $rawColor,
                $this->colorOptionsProvider->getOptions($context, $fieldDefinition)
            );

            if ($selectValue !== null) {
                return $selectValue;
            }
        }

        return $this->resolveLinkedColorFromObjectCode($this->extractColorCode($rawColor));
    }

    /**
     * @param array<int, array{key?: mixed, value?: mixed}> $options
     */
    private function resolveLinkedColorFromOptions(string $rawColor, array $options): ?string
    {
        $rawValue = trim($rawColor);
        $rawLabel = $this->normalizeColorLabel($rawColor);
        $rawCode = $this->normalizeColorCode($this->extractColorCode($rawColor));

        foreach ($options as $option) {
            $optionValue = trim((string) ($option['value'] ?? ''));
            if ($optionValue !== '' && $optionValue === $rawValue) {
                return $optionValue;
            }

            $optionLabel = (string) ($option['key'] ?? '');
            if ($optionLabel !== '' && $this->normalizeColorLabel($optionLabel) === $rawLabel) {
                return $optionValue;
            }

            if ($optionLabel !== '' && $this->normalizeColorCode($this->extractColorCode($optionLabel)) === $rawCode) {
                return $optionValue;
            }
        }

        return null;
    }

    private function resolveLinkedColorFromObjectCode(string $colorCode): ?string
    {
        $colorCode = trim($colorCode);
        if ($colorCode === '') {
            return null;
        }

        $listing = new ColorListing();
        if (method_exists($listing, 'setUnpublished')) {
            $listing->setUnpublished(true);
        }

        $listing->setCondition('`code` = ?', [$colorCode]);
        $listing->setLimit(1);

        $colors = $listing->load();
        if ($colors === []) {
            return null;
        }

        return (string) $colors[0]->getId();
    }

    /**
     * @return list<string>
     */
    private function splitImportList(string $value): array
    {
        $parts = preg_split('/[;,|]+/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
    }

    private function extractColorCode(string $value): string
    {
        $parts = preg_split('/\s+-\s+/', $value, 2);

        return trim((string) ($parts[0] ?? $value));
    }

    private function normalizeColorLabel(string $value): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }

    private function normalizeColorCode(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', ' ', trim($value)));
    }

    private function normalizeToken(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/', '', trim($value)));
    }

    private function getFieldValue(Concrete $object, string $fieldName): mixed
    {
        $getter = 'get' . ucfirst($fieldName);

        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        return $object->getObjectVar($fieldName);
    }

    private function setFieldValue(Concrete $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);

        if (method_exists($object, $setter)) {
            $object->$setter($value);

            return;
        }

        $object->setObjectVar($fieldName, $value);
    }
}
