<?php
declare(strict_types=1);

namespace App\Service;

use JsonException;
use RuntimeException;
use ZipArchive;

final class PrestaShopExportConverter
{
    /**
     * @return array{
     *     families: list<array<string, mixed>>,
     *     models: list<array<string, mixed>>,
     *     frames: list<array<string, mixed>>,
     *     report: array<string, mixed>
     * }
     */
    public function convert(
        string $inputPath,
        string $parentPath = '/Imports/ProductHierarchy/Families',
        ?int $modelLimit = null,
    ): array {
        if ($modelLimit !== null && $modelLimit < 1) {
            throw new RuntimeException('Model limit must be a positive integer.');
        }

        $source = $this->openSource($inputPath);

        try {
            $familyConfig = $source->readJson('config/CfgProductFamilies.json');
            $modelConfig = $source->readJson('config/CfgModels.json');
            $articleGroupsByCode = $this->indexByCode($source->readOptionalJson('config/CfgProductArticleGroup.json'));
            $categoriesByCode = $this->indexByCode($source->readOptionalJson('config/CfgProductCategories.json'));
            $linesByCode = $this->indexByCode($source->readOptionalJson('config/CfgProductLines.json'));
            $materialsByCode = $this->indexByCode($source->readOptionalJson('config/CfgProductMaterials.json'));
            $thicknessByCode = $this->indexByCode($source->readOptionalJson('config/CfgProductThickness.json'));
            $colorsByCode = $this->indexColorsByCode($source->readOptionalJson('config/TheoColors.json'));
            $productFiles = $source->listProductFiles();
            $productCodeCounts = [];
            $sourceProductCount = 0;

            foreach ($productFiles as $productFile) {
                $productBatch = $source->readJson($productFile);
                $sourceProductCount += count($productBatch);
                foreach ($productBatch as $product) {
                    $code = $this->stringValue($product['ProductCode'] ?? null);
                    if ($code !== '') {
                        $productCodeCounts[$code] = ($productCodeCounts[$code] ?? 0) + 1;
                    }
                }
            }

            $duplicateProductCodes = array_fill_keys(
                array_keys(array_filter($productCodeCounts, static fn (int $count): bool => $count > 1)),
                true
            );
            $familyCountsByModel = [];
            $modelValueCounts = [];

            foreach ($productFiles as $productFile) {
                foreach ($source->readJson($productFile) as $product) {
                    $productCode = $this->stringValue($product['ProductCode'] ?? null);
                    $familyCode = $this->stringValue($product['UDFs']['Family'] ?? null);
                    $modelCode = $this->stringValue($product['UDFs']['Model'] ?? null);
                    if (isset($duplicateProductCodes[$productCode]) || $modelCode === '') {
                        continue;
                    }

                    foreach (['Collection', 'Material'] as $field) {
                        $value = $this->stringValue($product['UDFs'][$field] ?? null);
                        if ($value !== '') {
                            $modelValueCounts[$modelCode][$field][$value] =
                                ($modelValueCounts[$modelCode][$field][$value] ?? 0) + 1;
                        }
                    }

                    if ($familyCode !== '' && $familyCode !== '0') {
                        $familyCountsByModel[$modelCode][$familyCode] =
                            ($familyCountsByModel[$modelCode][$familyCode] ?? 0) + 1;
                    }
                }
            }

            $familiesByCode = $this->indexByCode($familyConfig);
            $modelsByCode = $this->indexByCode($modelConfig);
            [$selectedFamilyByModel, $modelFamilyConflicts] = $this->selectFamiliesForModels($familyCountsByModel);

            $families = [];
            foreach ($familiesByCode as $code => $family) {
                $code = (string) $code;
                if ($code === '0') {
                    continue;
                }

                $families[] = [
                    'family_code' => $code,
                    'family_name' => $this->stringValue($family['Name'] ?? null) ?: $code,
                    'import_parent_path' => rtrim($parentPath, '/'),
                ];
            }

            $models = [];
            $modelsWithoutFamily = [];
            $finalProductDetailsByModel = [];
            foreach ($productFiles as $productFile) {
                foreach ($source->readJson($productFile) as $product) {
                    $productCode = $this->stringValue($product['ProductCode'] ?? null);
                    $familyCode = $this->stringValue($product['UDFs']['Family'] ?? null);
                    $modelCode = $this->stringValue($product['UDFs']['Model'] ?? null);
                    if (isset($duplicateProductCodes[$productCode]) || $modelCode === '') {
                        continue;
                    }
                    if (!isset($selectedFamilyByModel[$modelCode]) || (string) $selectedFamilyByModel[$modelCode] !== $familyCode) {
                        continue;
                    }

                    $colors = $this->normalizeColors($product['Colors'] ?? [], $colorsByCode);
                    $combiCode = $colors[0]['combi_code'] ?? '';
                    $colorCodes = array_values(array_unique(array_column($colors, 'color_code')));
                    if ($combiCode === '' || $colorCodes === []) {
                        continue;
                    }

                    $finalProductDetailsByModel[$modelCode][$combiCode] = [
                        'main_color_code' => $combiCode,
                        'color_codes' => $colorCodes,
                    ];
                }
            }

            foreach ($modelsByCode as $code => $model) {
                $code = (string) $code;
                $familyCode = $selectedFamilyByModel[$code] ?? null;
                if ($familyCode === null || !isset($familiesByCode[$familyCode])) {
                    $modelsWithoutFamily[] = $code;
                    continue;
                }

                $materialCode = $this->mostCommonValue($modelValueCounts[$code]['Material'] ?? []);
                $material = $materialsByCode[$materialCode] ?? [];

                $models[] = [
                    'model_code' => $code,
                    'model_name' => $this->stringValue($model['Name'] ?? null) ?: $code,
                    'parent_family_code' => $familyCode,
                    'frame_base_code' => $code,
                    'series_code' => $this->mostCommonValue($modelValueCounts[$code]['Collection'] ?? []),
                    'material_code' => $materialCode,
                    'material_name' => $this->stringValue($material['Name'] ?? null) ?: $materialCode,
                    'material' => $this->stringValue($material['Name'] ?? null) ?: $materialCode,
                    'description' => null,
                    'final_product_details' => array_values($finalProductDetailsByModel[$code] ?? []),
                ];
            }

            $limitedModelCodes = null;
            if ($modelLimit !== null) {
                $models = array_slice($models, 0, $modelLimit);
                $limitedModelCodes = array_fill_keys(array_column($models, 'model_code'), true);
                $limitedFamilyCodes = array_fill_keys(array_column($models, 'parent_family_code'), true);
                $families = array_values(array_filter(
                    $families,
                    static fn (array $family): bool => isset($limitedFamilyCodes[$family['family_code'] ?? null])
                ));
            }

            $frames = [];
            $skipped = [
                'duplicate_product_code' => [],
                'unclassified_family' => [],
                'missing_model' => [],
                'unknown_model' => [],
                'family_mismatch' => [],
            ];

            foreach ($productFiles as $productFile) {
                foreach ($source->readJson($productFile) as $product) {
                    $productCode = $this->stringValue($product['ProductCode'] ?? null);
                    $familyCode = $this->stringValue($product['UDFs']['Family'] ?? null);
                    $modelCode = $this->stringValue($product['UDFs']['Model'] ?? null);

                    if (isset($duplicateProductCodes[$productCode])) {
                        $skipped['duplicate_product_code'][$productCode] = true;
                        continue;
                    }
                    if ($familyCode === '' || $familyCode === '0') {
                        $skipped['unclassified_family'][] = $productCode;
                        continue;
                    }
                    if ($modelCode === '') {
                        $skipped['missing_model'][] = $productCode;
                        continue;
                    }
                    if ($limitedModelCodes !== null && !isset($limitedModelCodes[$modelCode])) {
                        continue;
                    }
                    if (!isset($modelsByCode[$modelCode], $selectedFamilyByModel[$modelCode])) {
                        $skipped['unknown_model'][] = $productCode;
                        continue;
                    }
                    if ((string) $selectedFamilyByModel[$modelCode] !== $familyCode) {
                        $skipped['family_mismatch'][] = [
                            'frame_code' => $productCode,
                            'model_code' => $modelCode,
                            'source_family_code' => $familyCode,
                            'selected_family_code' => (string) $selectedFamilyByModel[$modelCode],
                        ];
                        continue;
                    }

                    $udfs = is_array($product['UDFs'] ?? null) ? $product['UDFs'] : [];
                    $colors = $this->normalizeColors($product['Colors'] ?? [], $colorsByCode);
                    $articleGroupCode = $this->stringValue($udfs['ArticleGroup'] ?? null);
                    $categoryCode = $this->stringValue($udfs['Category'] ?? null);
                    $lineCode = $this->stringValue($udfs['Line'] ?? null);
                    $materialCode = $this->stringValue($udfs['Material'] ?? null);
                    $thicknessCode = $this->stringValue($udfs['Thickness'] ?? null);

                    $frames[] = [
                        'frame_code' => $productCode,
                        'frame_name' => $this->productName($product),
                        'frame_names' => $this->productNames($product),
                        'parent_model_code' => $modelCode,
                        'family_code' => $familyCode,
                        'model_code' => $modelCode,
                        'gtin' => $this->stringValue($udfs['GTIN'] ?? null),
                        'collection' => $this->stringValue($udfs['Collection'] ?? null),
                        'article_group_code' => $articleGroupCode,
                        'article_group_name' => $this->lookupName($articleGroupsByCode, $articleGroupCode),
                        'category_code' => $categoryCode,
                        'category_name' => $this->lookupName($categoriesByCode, $categoryCode),
                        'line_code' => $lineCode,
                        'line_name' => $this->lookupName($linesByCode, $lineCode),
                        'thickness_code' => $thicknessCode,
                        'thickness_name' => $this->lookupName($thicknessByCode, $thicknessCode),
                        'material_code' => $materialCode,
                        'material_name' => $this->lookupName($materialsByCode, $materialCode),
                        'is_active' => (bool) ($product['IsActive'] ?? false),
                        'can_be_sold' => filter_var($product['CanBeSold'] ?? false, FILTER_VALIDATE_BOOL),
                        'colors' => $colors,
                        'art_base_code' => $modelCode,
                        // Keep this empty until the Pimcore main-color convention is confirmed. Setting it
                        // to CombiCode currently makes the frame save subscriber rewrite ProductCode.
                        'main_color_code' => '',
                        'series_code' => $this->stringValue($udfs['Collection'] ?? null),
                        'ecom_file_name' => $this->stringValue($udfs['Image'] ?? null),
                        'exchange_code' => '',
                        'item_group_numbers' => $this->nonEmptyList($articleGroupCode),
                        'supplier_code' => '',
                        'composed_color_codes' => array_values(array_unique(array_column($colors, 'color_code'))),
                        'component_item_codes' => [],
                        'pos_material_item_codes' => [],
                        'service_part_codes' => [],
                        'downloadable_asset_keys' => [],
                        'image_gallery_asset_paths' => [],
                        'attachment_asset_paths' => [],
                        'source' => [
                            'gtin' => $this->stringValue($udfs['GTIN'] ?? null),
                            'family_code' => $familyCode,
                            'material' => $materialCode,
                            'shape' => $this->stringValue($udfs['Shape'] ?? null),
                            'line' => $lineCode,
                            'thickness' => $thicknessCode,
                            'category' => $categoryCode,
                            'tariff_code' => $this->stringValue($udfs['TarifCode'] ?? null),
                            'is_active' => (bool) ($product['IsActive'] ?? false),
                            'can_be_sold' => filter_var($product['CanBeSold'] ?? false, FILTER_VALIDATE_BOOL),
                            'combi_code' => $colors[0]['combi_code'] ?? '',
                            'colors' => $colors,
                        ],
                    ];
                }
            }

            $skipped['duplicate_product_code'] = array_keys($skipped['duplicate_product_code']);
        } finally {
            $source->close();
        }

        return [
            'families' => $families,
            'models' => $models,
            'frames' => $frames,
            'report' => [
                'summary' => [
                    'source_family_records' => count($familyConfig),
                    'source_model_records' => count($modelConfig),
                    'source_product_records' => $sourceProductCount,
                    'output_family_records' => count($families),
                    'output_model_records' => count($models),
                    'output_frame_records' => count($frames),
                ],
                'decisions' => [
                    'model_parent_rule' => 'The family with the highest number of frame references is selected.',
                    'family_mismatch_rule' => 'Frames referencing another family for that model are skipped.',
                    'duplicate_product_rule' => 'All records using a duplicated ProductCode are skipped.',
                    'unclassified_rule' => 'Products with Family "0", an empty family, or an empty model are skipped.',
                    'main_color_rule' => 'main_color_code is left empty to preserve ProductCode. CombiCode is retained under source.combi_code; ColorCode values populate composed colors.',
                    'manual_products' => 'manual_products.json is intentionally excluded because its records are not family/model frames.',
                    'model_limit_rule' => $modelLimit === null
                        ? 'No model limit was applied.'
                        : sprintf('Only the first %d resolved model(s), their families, and their child frames are included.', $modelLimit),
                ],
                'model_family_conflicts' => $modelFamilyConflicts,
                'models_without_resolved_family' => $modelsWithoutFamily,
                'duplicate_product_codes' => array_keys($duplicateProductCodes),
                'skipped_frames' => $skipped,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, array<string, mixed>>
     */
    private function indexByCode(array $records): array
    {
        $indexed = [];
        foreach ($records as $record) {
            $code = $this->stringValue($record['Code'] ?? null);
            if ($code !== '') {
                $indexed[$code] = $record;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string, array<string, int>> $familyCountsByModel
     *
     * @return array{array<string, string>, list<array<string, mixed>>}
     */
    private function selectFamiliesForModels(array $familyCountsByModel): array
    {
        $selected = [];
        $conflicts = [];

        foreach ($familyCountsByModel as $modelCode => $familyCounts) {
            uksort($familyCounts, static function (string $left, string $right) use ($familyCounts): int {
                return $familyCounts[$right] <=> $familyCounts[$left] ?: $left <=> $right;
            });

            $familyCode = array_key_first($familyCounts);
            if ($familyCode === null) {
                continue;
            }

            $modelCode = (string) $modelCode;
            $familyCode = (string) $familyCode;
            $selected[$modelCode] = $familyCode;
            if (count($familyCounts) > 1) {
                $conflicts[] = [
                    'model_code' => $modelCode,
                    'selected_family_code' => $familyCode,
                    'references_by_family' => $familyCounts,
                ];
            }
        }

        return [$selected, $conflicts];
    }

    /**
     * @param array<string, int> $counts
     */
    private function mostCommonValue(array $counts): string
    {
        arsort($counts);

        return (string) (array_key_first($counts) ?? '');
    }

    /**
     * @param array<string, array<string, mixed>> $colorsByCode
     *
     * @return list<array{combi_code: string, color_code: string, color_name: string, generic_color: string, order_nr: int}>
     */
    private function normalizeColors(mixed $colors, array $colorsByCode = []): array
    {
        if (!is_array($colors)) {
            return [];
        }

        $normalized = [];
        foreach ($colors as $color) {
            if (!is_array($color) || strtoupper($this->stringValue($color['IsRelevant'] ?? 'Y')) === 'N') {
                continue;
            }

            $colorCode = $this->stringValue($color['ColorCode'] ?? null);
            if ($colorCode === '') {
                continue;
            }

            $colorConfig = $colorsByCode[$colorCode] ?? [];
            $normalized[] = [
                'combi_code' => $this->stringValue($color['CombiCode'] ?? null),
                'color_code' => $colorCode,
                'color_name' => $this->stringValue($colorConfig['ColorName'] ?? null),
                'generic_color' => $this->stringValue($colorConfig['GenericColor'] ?? null),
                'order_nr' => (int) ($color['OrderNr'] ?? PHP_INT_MAX),
            ];
        }

        usort($normalized, static fn (array $left, array $right): int => $left['order_nr'] <=> $right['order_nr']);

        return $normalized;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return array<string, array<string, mixed>>
     */
    private function indexColorsByCode(array $records): array
    {
        $indexed = [];
        foreach ($records as $record) {
            $code = $this->stringValue($record['ColorCode'] ?? null);
            if ($code !== '') {
                $indexed[$code] = $record;
            }
        }

        return $indexed;
    }

    /**
     * @param array<string, array<string, mixed>> $records
     */
    private function lookupName(array $records, string $code): string
    {
        return $this->stringValue($records[$code]['Name'] ?? null);
    }

    /**
     * @param array<string, mixed> $product
     */
    private function productName(array $product): string
    {
        $names = $this->productNames($product);

        foreach (['EN', 'en', 'NL', 'nl', 'FR', 'fr'] as $language) {
            $name = $this->stringValue($names[$language] ?? null);
            if ($name !== '') {
                return $name;
            }
        }

        return $this->stringValue($product['ProductCode'] ?? null);
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, string>
     */
    private function productNames(array $product): array
    {
        $names = is_array($product['ProductName'] ?? null) ? $product['ProductName'] : [];
        $normalized = [];
        foreach ($names as $language => $name) {
            if (is_scalar($language)) {
                $normalized[strtolower((string) $language)] = $this->stringValue($name);
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private function nonEmptyList(mixed $value): array
    {
        $value = $this->stringValue($value);

        return $value === '' ? [] : [$value];
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function openSource(string $inputPath): PrestaShopExportSource
    {
        if (is_dir($inputPath)) {
            return PrestaShopExportSource::fromDirectory($inputPath);
        }

        if (is_file($inputPath) && strtolower(pathinfo($inputPath, PATHINFO_EXTENSION)) === 'zip') {
            return PrestaShopExportSource::fromZip($inputPath);
        }

        throw new RuntimeException(sprintf('Input must be an export directory or ZIP file: %s', $inputPath));
    }
}

final class PrestaShopExportSource
{
    private function __construct(
        private readonly ?string $directory,
        private readonly ?ZipArchive $zip,
        private readonly string $prefix,
    ) {
    }

    public static function fromDirectory(string $directory): self
    {
        $directory = rtrim($directory, '/');
        if (is_dir($directory . '/export/config')) {
            $directory .= '/export';
        }
        if (!is_dir($directory . '/config') || !is_dir($directory . '/products')) {
            throw new RuntimeException('Export directory must contain config/ and products/ folders.');
        }

        return new self($directory, null, '');
    }

    public static function fromZip(string $path): self
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException(sprintf('Unable to open ZIP file: %s', $path));
        }

        $prefix = $zip->locateName('export/config/CfgProductFamilies.json') !== false ? 'export/' : '';

        return new self(null, $zip, $prefix);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readJson(string $relativePath): array
    {
        $contents = $this->directory !== null
            ? @file_get_contents($this->directory . '/' . $relativePath)
            : $this->zip?->getFromName($this->prefix . $relativePath);

        if (!is_string($contents)) {
            throw new RuntimeException(sprintf('Unable to read export file: %s', $relativePath));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid JSON in %s: %s', $relativePath, $exception->getMessage()), 0, $exception);
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new RuntimeException(sprintf('Expected a JSON array in export file: %s', $relativePath));
        }

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readOptionalJson(string $relativePath): array
    {
        $exists = $this->directory !== null
            ? is_file($this->directory . '/' . $relativePath)
            : $this->zip?->locateName($this->prefix . $relativePath) !== false;

        return $exists ? $this->readJson($relativePath) : [];
    }

    /**
     * @return list<string>
     */
    public function listProductFiles(): array
    {
        $files = [];
        if ($this->directory !== null) {
            foreach (glob($this->directory . '/products/Product_TransactionStep_*.json') ?: [] as $path) {
                $files[] = 'products/' . basename($path);
            }
        } else {
            for ($index = 0; $index < ($this->zip?->numFiles ?? 0); $index++) {
                $name = $this->zip?->getNameIndex($index);
                if (
                    is_string($name)
                    && preg_match('#^' . preg_quote($this->prefix, '#') . 'products/(Product_TransactionStep_[^/]+\.json)$#', $name, $matches)
                ) {
                    $files[] = 'products/' . $matches[1];
                }
            }
        }

        sort($files, SORT_NATURAL);
        if ($files === []) {
            throw new RuntimeException('No Product_TransactionStep_*.json files found in the export.');
        }

        return $files;
    }

    public function close(): void
    {
        $this->zip?->close();
    }
}
