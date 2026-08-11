<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataImporter;

use Codeception\Test\Unit;
use Symfony\Component\Yaml\Yaml;

final class FamilyAdditionalDataImporterConfigTest extends Unit
{
    public function testUpdatesExistingFamiliesAndResolvesRelations(): void
    {
        $configuration = Yaml::parseFile(PROJECT_ROOT . '/var/config/data_hub/FamilyAdditionalDataImport.yaml');
        $importer = $configuration['pimcore_data_hub']['configurations']['FamilyAdditionalDataImport'];

        self::assertSame('/Datasource Files/Family-additional-info.xlsx', $importer['loaderConfig']['settings']['assetPath']);
        self::assertSame([
            'type' => 'xlsx',
            'settings' => [
                'skipFirstRow' => true,
                'sheetName' => 'Sheet1',
            ],
        ], $importer['interpreterConfig']);
        self::assertSame('family', $importer['resolverConfig']['dataObjectClassId']);
        self::assertSame('attributeWithTrimFallback', $importer['resolverConfig']['loadingStrategy']['type']);
        self::assertSame('1', $importer['resolverConfig']['loadingStrategy']['settings']['dataSourceIndex']);
        self::assertSame('code', $importer['resolverConfig']['loadingStrategy']['settings']['attributeName']);
        self::assertSame('doNotCreate', $importer['resolverConfig']['createLocationStrategy']['type']);
        self::assertSame('noChange', $importer['resolverConfig']['locationUpdateStrategy']['type']);
        self::assertSame('1', $importer['processingConfig']['idDataIndex']);
        self::assertFalse($importer['processingConfig']['cleanup']['doCleanup']);

        $expectedDirectMappings = [
            'Start Date' => ['8', 'startDate'],
            'Launch Period' => ['9', 'launchPeriod'],
            'Launch Year' => ['10', 'launchYear'],
            'Exchangeable Branches' => ['13', 'exchangeableBranches'],
            'Exchangeable Branches Partial' => ['14', 'exchangeableBranchesPartial'],
            'Magic Mechanism Score' => ['29', 'magicMechanismScore'],
        ];

        foreach ($expectedDirectMappings as $label => [$sourceIndex, $fieldName]) {
            $mapping = $this->mappingByLabel($importer['mappingConfig'], $label);
            self::assertSame([$sourceIndex], $mapping['dataSourceIndex']);
            self::assertSame($fieldName, $mapping['dataTarget']['settings']['fieldName']);
            self::assertFalse($mapping['dataTarget']['settings']['writeIfSourceIsEmpty']);
        }

        $designerRelation = $this->mappingByLabel($importer['mappingConfig'], 'Designers Relation');
        self::assertRelationLookup($designerRelation, '11', 'designersRelation', 'designer', 'key');
        self::assertSame(
            ['explode', 'trim', 'conditionalConversion', 'loadDataObject'],
            array_column($designerRelation['transformationPipeline'], 'type')
        );

        $designers = $this->mappingByLabel($importer['mappingConfig'], 'Designers');
        self::assertSame('designers', $designers['dataTarget']['settings']['fieldName']);
        self::assertSame(
            ['trim', 'conditionalConversion', 'loadDataObject', 'objectField', 'asArray'],
            array_column($designers['transformationPipeline'], 'type')
        );
        self::assertSame('id', $designers['transformationPipeline'][3]['settings']['attribute']);

        $suppliers = $this->mappingByLabel($importer['mappingConfig'], 'Suppliers');
        self::assertRelationLookup($suppliers, '15', 'suppliers', 'supplier', 'code');
        self::assertSame(['explode', 'trim', 'loadDataObject'], array_column($suppliers['transformationPipeline'], 'type'));
        self::assertSame(',', $suppliers['transformationPipeline'][0]['settings']['delimiter']);

        foreach (['Launch Year', 'Magic Mechanism Score'] as $label) {
            $mapping = $this->mappingByLabel($importer['mappingConfig'], $label);
            self::assertTrue($mapping['transformationPipeline'][0]['settings']['returnNullIfEmpty']);
        }
    }

    /**
     * @param array<string, mixed> $mapping
     */
    private function assertRelationLookup(
        array $mapping,
        string $sourceIndex,
        string $fieldName,
        string $classId,
        string $attributeName,
    ): void {
        self::assertSame([$sourceIndex], $mapping['dataSourceIndex']);
        self::assertSame('manyToManyRelation', $mapping['dataTarget']['type']);
        self::assertSame($fieldName, $mapping['dataTarget']['settings']['fieldName']);
        self::assertSame('replace', $mapping['dataTarget']['settings']['overwriteMode']);
        self::assertFalse($mapping['dataTarget']['settings']['writeIfSourceIsEmpty']);

        $lookups = array_values(array_filter(
            $mapping['transformationPipeline'],
            static fn (array $operator): bool => ($operator['type'] ?? null) === 'loadDataObject'
        ));
        self::assertCount(1, $lookups);
        $lookup = $lookups[0];
        self::assertSame('loadDataObject', $lookup['type']);
        self::assertSame('attribute', $lookup['settings']['loadStrategy']);
        self::assertSame($classId, $lookup['settings']['attributeDataObjectClassId']);
        self::assertSame($attributeName, $lookup['settings']['attributeName']);
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
