<?php

declare(strict_types=1);

namespace App\Service;

final class SapSyncResolver
{
    /**
     * Frame components replace Model defaults with the same component type.
     * Components without a type are kept and never accidentally overwrite another component.
     *
     * @template T
     * @param list<T> $modelComponents
     * @param list<T> $frameComponents
     * @param callable(T): list<string> $types
     * @return list<T>
     */
    public function effectiveComponents(array $modelComponents, array $frameComponents, callable $types): array
    {
        $result = [];
        $positions = [];

        foreach ($modelComponents as $component) {
            $position = count($result);
            $result[] = $component;
            foreach ($this->normalisedTypes($types($component)) as $type) {
                $positions[$type] ??= $position;
            }
        }

        foreach ($frameComponents as $component) {
            $componentTypes = $this->normalisedTypes($types($component));
            $replace = null;
            foreach ($componentTypes as $type) {
                if (isset($positions[$type])) {
                    $replace = $positions[$type];
                    break;
                }
            }

            if ($replace === null) {
                $replace = count($result);
                $result[] = $component;
            } else {
                $result[$replace] = $component;
            }
            foreach ($componentTypes as $type) {
                $positions[$type] = $replace;
            }
        }

        return array_values($result);
    }

    /**
     * Resolve the documented Frame → Model → Family → Supplier → Process precedence.
     */
    public function leadTime(
        int|float|string|null $frame,
        int|float|string|null $model,
        int|float|string|null $family,
        int|float|string|null $supplier,
        int|float|string|null $processDefault,
    ): ?float {
        foreach ([$frame, $model, $family, $supplier, $processDefault] as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /** @param list<string> $types @return list<string> */
    private function normalisedTypes(array $types): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $type): string => strtolower(trim($type)),
            $types,
        ))));
    }
}
