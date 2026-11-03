<?php

declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

/** Builds the canonical, read-only SAP representation used by checks and writers. */
final class SapSyncProjectionService
{
    public function __construct(private readonly SapSyncResolver $resolver)
    {
    }

    /**
     * @return array{expected: array<string, mixed>, missing: list<string>}
     */
    public function project(AbstractObject $frame): array
    {
        $model = $frame->getParent();
        $family = $model instanceof AbstractObject ? $model->getParent() : null;
        $missing = [];

        $itemCode = $this->string($this->get($frame, 'sapItemCode'));
        $itemName = $this->string($this->get($frame, 'sapItemName'));
        $materialType = $this->string($this->get($frame, 'materialType'));
        if ($itemCode === '') {
            $missing[] = 'P1-6 sapItemCode';
        }
        if ($itemName === '') {
            $missing[] = 'P1-6 sapItemName';
        }
        if ($materialType === '') {
            $missing[] = 'P1-8 materialType';
        }

        $colors = [];
        foreach ($this->relationMetadata($this->get($frame, 'composedColors')) as $position => $metadata) {
            $sapCode = $this->string($metadata->getSapColorCode());
            if ($sapCode === '') {
                $missing[] = sprintf('P1-7 composedColors[%d].sapColorCode', $position + 1);
            }
            if ($metadata->getQty() === null) {
                $missing[] = sprintf('P1-7 composedColors[%d].qty', $position + 1);
            }
            $colors[] = [
                'sapColorCode' => $sapCode,
                'orderNr' => $metadata->getOrderNr() ?? $position + 1,
                'comment' => $this->string($metadata->getComment()),
                'qty' => $metadata->getQty() === null ? null : (float) $metadata->getQty(),
            ];
        }

        $modelComponents = $model instanceof AbstractObject ? $this->componentObjects($this->get($model, 'components')) : [];
        $frameComponents = $this->componentObjects($this->get($frame, 'components'));
        $components = $this->resolver->effectiveComponents(
            $modelComponents,
            $frameComponents,
            fn (AbstractObject $component): array => $this->strings($this->get($component, 'itemType')),
        );
        $componentRows = [];
        foreach ($components as $component) {
            $process = $this->get($component, 'process');
            $quantity = $this->get($component, 'quantity');
            if (!$process instanceof AbstractObject) {
                $missing[] = sprintf('P1-4 component #%d process', $component->getId());
            }
            if ($quantity === null || !is_numeric($quantity)) {
                $missing[] = sprintf('P1-2 component #%d quantity', $component->getId());
            }
            $componentRows[] = [
                'itemCode' => $this->string($this->get($component, 'itemCode')),
                'itemName' => $this->string($this->get($component, 'itemName')),
                'itemType' => $this->strings($this->get($component, 'itemType')),
                'quantity' => is_numeric($quantity) ? (float) $quantity : null,
                'process' => $process instanceof AbstractObject
                    ? $this->string($this->get($process, 'code'))
                    : '',
            ];
        }

        return [
            'expected' => [
                'itemCode' => $itemCode,
                'itemName' => $itemName,
                'sapFamily' => $this->string($this->get($frame, 'sapFamily')),
                'materialType' => $materialType,
                'sapActive' => (bool) $this->get($frame, 'sapActive'),
                'isNew' => (bool) $this->get($frame, 'isNew'),
                'ecommerceDescription' => $this->string($this->get($frame, 'ecommerceDescription')),
                'launchYear' => $model instanceof AbstractObject ? $this->get($model, 'launchYear') : null,
                'launchPeriod' => $model instanceof AbstractObject ? $this->string($this->get($model, 'launchPeriod')) : '',
                'family' => $family instanceof AbstractObject ? $this->string($this->get($family, 'code')) : '',
                'combiColor' => $this->relationCode($this->get($frame, 'combiColor')),
                'colors' => $colors,
                'components' => $componentRows,
            ],
            'missing' => array_values(array_unique($missing)),
        ];
    }

    /** @return list<ObjectMetadata> */
    private function relationMetadata(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, static fn (mixed $item): bool => $item instanceof ObjectMetadata)) : [];
    }

    /** @return list<AbstractObject> */
    private function componentObjects(mixed $value): array
    {
        $objects = [];
        foreach (is_array($value) ? $value : [] as $item) {
            $object = $item instanceof ObjectMetadata ? $item->getObject() : $item;
            if ($object instanceof AbstractObject) {
                $objects[] = $object;
            }
        }

        return $objects;
    }

    private function relationCode(mixed $value): string
    {
        return $value instanceof AbstractObject ? $this->string($this->get($value, 'code')) : '';
    }

    private function get(AbstractObject $object, string $field): mixed
    {
        $getter = 'get'.ucfirst($field);

        return method_exists($object, $getter) ? $object->{$getter}() : null;
    }

    /** @return list<string> */
    private function strings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn (mixed $item): string => $this->string($item), $value)));
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
