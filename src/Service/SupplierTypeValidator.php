<?php

declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;

final class SupplierTypeValidator
{
    /**
     * @param list<mixed> $links
     * @return list<string>
     */
    public function violations(array $links): array
    {
        $violations = [];

        foreach ($links as $link) {
            if (!$link instanceof ObjectMetadata) {
                continue;
            }
            $supplier = $link->getObject();
            if (!$supplier instanceof AbstractObject) {
                continue;
            }

            $selectedTypes = $this->selectedTypes($link);
            if ($selectedTypes === []) {
                continue;
            }
            $allowed = $this->allowedProcessCodes($supplier);
            foreach ($selectedTypes as $selectedType) {
                if (isset($allowed[$this->normalize($selectedType)])) {
                    continue;
                }
                $violations[] = sprintf(
                    'Supplier %s does not have Process "%s". Allowed Processes: %s.',
                    $this->supplierLabel($supplier),
                    $selectedType,
                    $allowed === [] ? 'none' : implode(', ', array_values($allowed)),
                );
            }
        }

        return $violations;
    }

    /** @return array<string, string> normalized code => display code */
    public function allowedProcessCodes(AbstractObject $supplier): array
    {
        $codes = [];
        $processes = $this->get($supplier, 'process');
        foreach (is_array($processes) ? $processes : [] as $process) {
            $this->addProcessCode($codes, $process);
        }

        $details = $this->get($supplier, 'processDetails');
        if ($details instanceof Fieldcollection) {
            foreach ($details as $detail) {
                if (is_object($detail) && method_exists($detail, 'getProcess')) {
                    $this->addProcessCode($codes, $detail->getProcess());
                }
            }
        }

        uasort($codes, 'strnatcasecmp');

        return $codes;
    }

    /** @param array<string, string> $codes */
    private function addProcessCode(array &$codes, mixed $process): void
    {
        if (!$process instanceof AbstractObject) {
            return;
        }
        $code = trim((string) $this->get($process, 'code'));
        if ($code === '') {
            return;
        }
        $codes[$this->normalize($code)] = $code;
    }

    /** @return list<string> */
    private function selectedTypes(ObjectMetadata $link): array
    {
        try {
            $value = $link->getSupplierType();
        } catch (\Throwable) {
            return [];
        }
        $values = is_array($value) ? $value : (preg_split('/[,;]/', (string) $value) ?: []);

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
            $values,
        ))));
    }

    private function supplierLabel(AbstractObject $supplier): string
    {
        foreach (['code', 'name'] as $field) {
            $value = trim((string) $this->get($supplier, $field));
            if ($value !== '') {
                return $value;
            }
        }

        return sprintf('#%d', $supplier->getId());
    }

    private function get(AbstractObject $object, string $field): mixed
    {
        $getter = 'get'.ucfirst($field);

        return method_exists($object, $getter) ? $object->{$getter}() : null;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
