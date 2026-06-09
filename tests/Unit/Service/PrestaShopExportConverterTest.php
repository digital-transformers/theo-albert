<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PrestaShopExportConverter;
use Codeception\Test\Unit;

final class PrestaShopExportConverterTest extends Unit
{
    private string $exportDirectory;

    protected function _before(): void
    {
        $this->exportDirectory = sys_get_temp_dir() . '/prestashop-export-converter-' . bin2hex(random_bytes(6));
        mkdir($this->exportDirectory . '/config', 0777, true);
        mkdir($this->exportDirectory . '/products', 0777, true);
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->exportDirectory);
    }

    public function testConvertsHierarchyAndReportsConflictingFamilyReferences(): void
    {
        $this->writeJson('config/CfgProductFamilies.json', [
            ['Code' => 'family-a', 'Name' => 'Family A'],
            ['Code' => 'family-b', 'Name' => 'Family B'],
        ]);
        $this->writeJson('config/CfgModels.json', [
            ['Code' => 'MODEL', 'Name' => 'Model'],
        ]);
        $this->writeJson('products/Product_TransactionStep_1.json', [
            $this->product('MODEL-1', 'family-a', 'MODEL', '1', 'BLACK'),
            $this->product('MODEL-2', 'family-a', 'MODEL', '2', 'RED'),
            $this->product('MODEL-3', 'family-b', 'MODEL', '3', 'BLUE'),
        ]);

        $result = (new PrestaShopExportConverter())->convert($this->exportDirectory, '/Product Data/Families');

        self::assertCount(2, $result['families']);
        self::assertSame('family-a', $result['models'][0]['parent_family_code']);
        self::assertCount(2, $result['frames']);
        self::assertSame(['BLACK'], $result['frames'][0]['composed_color_codes']);
        self::assertSame('', $result['frames'][0]['main_color_code']);
        self::assertSame('1', $result['frames'][0]['source']['combi_code']);
        self::assertSame('family-b', $result['report']['skipped_frames']['family_mismatch'][0]['source_family_code']);
        self::assertSame('family-a', $result['report']['model_family_conflicts'][0]['selected_family_code']);
    }

    public function testSkipsUnclassifiedAndDuplicateProducts(): void
    {
        $this->writeJson('config/CfgProductFamilies.json', [
            ['Code' => 'family-a', 'Name' => 'Family A'],
        ]);
        $this->writeJson('config/CfgModels.json', [
            ['Code' => 'MODEL', 'Name' => 'Model'],
        ]);
        $this->writeJson('products/Product_TransactionStep_1.json', [
            $this->product('DUPLICATE', 'family-a', 'MODEL', '1', 'BLACK'),
            $this->product('DUPLICATE', 'family-a', 'MODEL', '2', 'RED'),
            $this->product('NO-FAMILY', '0', '', '', ''),
            $this->product('VALID', 'family-a', 'MODEL', '3', 'BLUE'),
        ]);

        $result = (new PrestaShopExportConverter())->convert($this->exportDirectory);

        self::assertSame(['VALID'], array_column($result['frames'], 'frame_code'));
        self::assertSame(['DUPLICATE'], $result['report']['duplicate_product_codes']);
        self::assertSame(['NO-FAMILY'], $result['report']['skipped_frames']['unclassified_family']);
    }

    /**
     * @return array<string, mixed>
     */
    private function product(
        string $code,
        string $family,
        string $model,
        string $combiCode,
        string $colorCode,
    ): array {
        return [
            'ProductCode' => $code,
            'ProductName' => ['EN' => $code . ' name'],
            'IsActive' => true,
            'CanBeSold' => true,
            'Colors' => $colorCode === '' ? [] : [[
                'CombiCode' => $combiCode,
                'ColorCode' => $colorCode,
                'OrderNr' => 1,
                'IsRelevant' => 'Y',
            ]],
            'UDFs' => [
                'Family' => $family,
                'Model' => $model,
                'Collection' => 'COLLECTION',
                'Material' => 'MATERIAL',
                'ArticleGroup' => '111',
                'GTIN' => '123',
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $value
     */
    private function writeJson(string $relativePath, array $value): void
    {
        file_put_contents(
            $this->exportDirectory . '/' . $relativePath,
            json_encode($value, JSON_THROW_ON_ERROR)
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
