<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ProductHierarchyGraphqlClient;
use App\Service\ProductHierarchySyncService;
use Codeception\Test\Unit;

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
