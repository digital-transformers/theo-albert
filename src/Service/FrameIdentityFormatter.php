<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

final class FrameIdentityFormatter
{
    public static function code(string $base, string $mainColor): string
    {
        if ($base === '' || $mainColor === '') {
            return $base . $mainColor;
        }

        return $base . (strlen($mainColor) < 3 ? '-' : ' ') . $mainColor;
    }

    public static function name(string $modelName, string $mainColor, array $colors): string
    {
        $primary = $acetate = [];
        foreach ($colors as $entry) {
            $color = $entry instanceof ObjectMetadata ? $entry->getObject() : $entry;
            if (!$color instanceof Color) {
                continue;
            }
            $data = $entry instanceof ObjectMetadata ? $entry->getData() : [];
            $label = trim((string) ($data['name'] ?? ''));
            $label = $label !== '' ? $label : trim((string) $color->getName());
            if ($color->getColorType() === 'acetate') {
                $acetate[] = [$label, (bool) ($data['relevant'] ?? false)];
            } else {
                $primary[] = [trim((string) $color->getCode()), $label];
            }
        }

        $biColor = count($primary) >= 2;
        $parts = $biColor ? array_column($primary, 0) : array_column($primary, 1);
        foreach ($acetate as [$label, $relevant]) {
            if (!$biColor || $relevant) {
                $parts[] = $label;
            }
        }
        $description = implode(' + ', array_filter($parts, static fn (string $part): bool => $part !== ''));
        if ($biColor && $description !== '') {
            $description = 'C ' . $description;
        }
        $padded = $mainColor === '' ? '' : str_pad($mainColor, 3, '0', STR_PAD_LEFT);

        return mb_strtoupper(implode(' ', array_filter([$modelName, $padded, $description], static fn (string $part): bool => $part !== '')));
    }
}
