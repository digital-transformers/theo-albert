<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataImporter;

use Codeception\Test\Unit;
use Symfony\Component\Yaml\Yaml;

final class AcetaatColorsImporterConfigTest extends Unit
{
    public function testImportsTheAcetateWorkbookAndMapsAlternateCodes(): void
    {
        $configuration = Yaml::parseFile(PROJECT_ROOT . '/var/config/data_hub/AcetaatColors.yaml');
        $importer = $configuration['pimcore_data_hub']['configurations']['AcetaatColors'];

        self::assertSame(
            '/Datasource Files/Acetate Colors + alternative.xlsx',
            $importer['loaderConfig']['settings']['assetPath']
        );
        self::assertSame([
            'type' => 'xlsx',
            'settings' => [
                'skipFirstRow' => true,
                'sheetName' => 'Sheet1',
            ],
        ], $importer['interpreterConfig']);
        self::assertSame('0', $importer['resolverConfig']['loadingStrategy']['settings']['dataSourceIndex']);
        self::assertSame('code', $importer['resolverConfig']['loadingStrategy']['settings']['attributeName']);
        self::assertTrue($importer['resolverConfig']['loadingStrategy']['settings']['includeUnpublished']);

        $alternateCodesMapping = $this->mappingByLabel($importer['mappingConfig'], 'AlternativeCodes');

        self::assertSame(['9'], $alternateCodesMapping['dataSourceIndex']);
        self::assertSame('alternateCodes', $alternateCodesMapping['dataTarget']['settings']['fieldName']);
        self::assertSame([
            ['settings' => ['delimiter' => ','], 'type' => 'explode'],
            ['settings' => ['mode' => 'both'], 'type' => 'trim'],
            ['settings' => ['glue' => "\n"], 'type' => 'combine'],
        ], $alternateCodesMapping['transformationPipeline']);
    }

    /**
     * @param list<array<string, mixed>> $mappings
     *
     * @return array<string, mixed>
     */
    private function mappingByLabel(array $mappings, string $label): array
    {
        foreach ($mappings as $mapping) {
            if (($mapping['label'] ?? null) === $label) {
                return $mapping;
            }
        }

        self::fail(sprintf('Mapping "%s" was not found.', $label));
    }
}
