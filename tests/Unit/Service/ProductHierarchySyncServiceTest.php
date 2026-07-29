<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ProductHierarchyGraphqlClient;
use App\Service\ProductHierarchySyncService;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Color;

final class ProductHierarchySyncServiceTest extends Unit
{
    public function testCreatesFamilyModelAndFrameInHierarchyOrder(): void
    {
        $client = new RecordingProductHierarchyClient();
        $service = new ProductHierarchySyncService($client);

        $result = $service->sync([
            'families' => [[
                'family_code' => 'FAMILY',
                'family_name' => 'Family',
                'import_parent_path' => '/Product Data/Families',
            ]],
            'models' => [[
                'model_code' => 'MODEL',
                'model_name' => 'Model',
                'parent_family_code' => 'FAMILY',
                'frame_base_code' => 'MODEL',
                'series_code' => 'SERIES',
                'material' => 'Metal',
                'description' => null,
            ]],
            'frames' => [[
                'frame_code' => 'FRAME-1',
                'frame_name' => 'Frame 1',
                'parent_model_code' => 'MODEL',
                'main_color_code' => '',
                'series_code' => 'SERIES',
                'ecom_file_name' => '',
                'exchange_code' => '',
            ]],
            'report' => [],
        ]);

        self::assertSame(1, $result['families']['created']);
        self::assertSame(1, $result['models']['created']);
        self::assertSame(1, $result['frames']['created']);
        self::assertSame(['createFamily', 'createModel', 'createFrame'], array_column($client->mutations, 'name'));
        self::assertSame(101, $client->mutations[1]['variables']['parentId']);
        self::assertSame(102, $client->mutations[2]['variables']['parentId']);
        self::assertSame(
            '/Product Data/Families/FAMILY/MODEL',
            $client->mutations[2]['variables']['input']['artBase']['fullpath']
        );
    }

    public function testReportsEveryUnmatchedColorWithItsSourceData(): void
    {
        $client = new RecordingProductHierarchyClient();
        $service = new ProductHierarchySyncService($client, static fn (string $code): null => null);
        $sourceColor = [
            'CombiCode' => '18',
            'IsRelevant' => 'Y',
            'OrderNr' => 1,
            'ColorCode' => 'ELMT 1256 40-60-80/10 PVT',
        ];

        $result = $service->sync([
            'families' => [],
            'models' => [],
            'frames' => [[
                'frame_code' => 'ANDY 18',
                'parent_model_code' => 'ANDY',
                'source' => [
                    'product_code' => 'ANDY -18',
                    'source_colors' => [$sourceColor],
                ],
            ], [
                'frame_code' => 'OTHER 18',
                'parent_model_code' => 'OTHER',
                'source' => [
                    'product_code' => 'OTHER-18',
                    'source_colors' => [$sourceColor],
                ],
            ]],
            'report' => [],
        ]);

        self::assertCount(2, $result['warnings']);
        self::assertSame('unmatched_color', $result['warnings'][0]['type']);
        self::assertSame('ANDY -18', $result['warnings'][0]['source_product_code']);
        self::assertSame($sourceColor, $result['warnings'][0]['source_color']);
        self::assertSame('OTHER-18', $result['warnings'][1]['source_product_code']);
    }

    public function testAlternateColorCodesUseTrimmedNonEmptyLines(): void
    {
        $service = new ProductHierarchySyncService(new RecordingProductHierarchyClient());
        $method = new \ReflectionMethod($service, 'alternateColorCodes');
        $method->setAccessible(true);

        self::assertSame(
            ['ABC 123', 'Second-Code'],
            $method->invoke($service, "  ABC 123  \r\n\r\nSecond-Code\nABC 123\n")
        );
    }

    public function testColorCodeNormalizationIgnoresWhitespace(): void
    {
        $service = new ProductHierarchySyncService(new RecordingProductHierarchyClient());
        $method = new \ReflectionMethod($service, 'normalizeColorCode');
        $method->setAccessible(true);

        self::assertSame('ab165960/10', $method->invoke($service, "  AB 1659\t60/10  "));
        self::assertSame('613bd0960/10tecko', $method->invoke($service, '613BD09 60/10  TECKO'));
    }

    public function testComposedColorMetadataPreservesSourceRelevance(): void
    {
        $color = (new Color())->setId(3011)->setName('AB1882');
        $service = new ProductHierarchySyncService(
            new RecordingProductHierarchyClient(),
            static fn (string $code): Color => $color
        );
        $method = new \ReflectionMethod($service, 'resolveComposedColors');
        $method->setAccessible(true);

        $metadata = $method->invoke($service, [[
            'color_code' => 'AB1882 60/10 PVT',
            'is_relevant' => false,
        ]]);

        self::assertCount(1, $metadata);
        self::assertFalse($metadata[0]->getRelevant());
    }
}

final class RecordingProductHierarchyClient extends ProductHierarchyGraphqlClient
{
    /**
     * @var list<array{name: string, variables: array<string, mixed>}>
     */
    public array $mutations = [];

    private int $nextId = 101;

    public function __construct()
    {
        parent::__construct('http://example.invalid', 'test');
    }

    public function execute(string $query, array $variables = []): array
    {
        foreach (['Family', 'Model', 'Frame'] as $entity) {
            $field = 'get' . $entity . 'Listing';
            if (str_contains($query, $field)) {
                return [$field => ['edges' => []]];
            }
        }

        foreach (['createFamily', 'createModel', 'createFrame'] as $mutation) {
            if (!str_contains($query, $mutation)) {
                continue;
            }

            $this->mutations[] = ['name' => $mutation, 'variables' => $variables];
            $id = $this->nextId++;
            $fullpath = match ($mutation) {
                'createFamily' => '/Product Data/Families/FAMILY',
                'createModel' => '/Product Data/Families/FAMILY/MODEL',
                default => '/Product Data/Families/FAMILY/MODEL/FRAME-1',
            };

            return [$mutation => [
                'success' => true,
                'message' => '',
                'output' => ['id' => $id, 'fullpath' => $fullpath],
            ]];
        }

        self::fail('Unexpected GraphQL operation.');
    }
}
