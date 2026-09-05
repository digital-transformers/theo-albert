<?php
declare(strict_types=1);

namespace App\Command;

use JsonException;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Color\Listing as ColorListing;
use Pimcore\Model\DataObject\Service as DataObjectService;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:colors:transfer',
    description: 'Export or import Color objects, their images and multi-color relations.',
)]
final class TransferColorsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'export or import')
            ->addArgument('file', InputArgument::REQUIRED, 'JSON transfer file')
            ->addOption('delete-missing', null, InputOption::VALUE_NONE, 'Delete destination colors absent from the transfer file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = strtolower(trim((string) $input->getArgument('action')));
        $file = (string) $input->getArgument('file');

        try {
            return match ($action) {
                'export' => $this->export($file, $io),
                'import' => $this->import($file, (bool) $input->getOption('delete-missing'), $io),
                default => throw new RuntimeException('Action must be either "export" or "import".'),
            };
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    private function export(string $file, SymfonyStyle $io): int
    {
        $listing = new ColorListing();
        $listing->setUnpublished(true);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        $colors = [];
        foreach ($listing as $color) {
            $image = $color->getImage();
            $colors[] = [
                'source_id' => $color->getId(),
                'full_path' => $color->getFullPath(),
                'published' => $color->getPublished(),
                'color_type' => $color->getColorType(),
                'generic_color' => $color->getGenericColor(),
                'code' => $color->getCode(),
                'name' => $color->getName(),
                'description' => $color->getDescription(),
                'alternate_codes' => $color->getAlternateCodes(),
                'image' => $image instanceof Image ? [
                    'full_path' => $image->getFullPath(),
                    'mime_type' => $image->getMimeType(),
                    'data' => base64_encode($image->getData()),
                ] : null,
                'multi_color_paths' => array_values(array_map(
                    static fn (Color $related): string => $related->getFullPath(),
                    array_filter(
                        $color->getMultiColor(['unpublished' => true]) ?: [],
                        static fn (mixed $related): bool => $related instanceof Color,
                    ),
                )),
            ];
        }

        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        $payload = json_encode([
            'format' => 'theo-color-transfer-v1',
            'exported_at' => date(DATE_ATOM),
            'colors' => $colors,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($file, $payload . "\n") === false) {
            throw new RuntimeException(sprintf('Unable to write "%s".', $file));
        }

        $io->success(sprintf('Exported %d colors to %s.', count($colors), $file));

        return Command::SUCCESS;
    }

    private function import(string $file, bool $deleteMissing, SymfonyStyle $io): int
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new RuntimeException(sprintf('Transfer file "%s" is not readable.', $file));
        }

        try {
            $payload = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The transfer file is not valid JSON.', 0, $exception);
        }
        if (($payload['format'] ?? null) !== 'theo-color-transfer-v1' || !is_array($payload['colors'] ?? null)) {
            throw new RuntimeException('The transfer file has an unsupported format.');
        }

        /** @var array<string, Color> $imported */
        $imported = [];
        $created = 0;
        $updated = 0;

        foreach ($payload['colors'] as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('The transfer file contains an invalid color row.');
            }
            $fullPath = $this->requiredPath($row['full_path'] ?? null);
            $color = Color::getByPath($fullPath);
            if (!$color instanceof Color) {
                $parentPath = dirname($fullPath);
                $parent = DataObjectService::createFolderByPath($parentPath);
                $color = new Color();
                $color->setParent($parent);
                $color->setKey(basename($fullPath));
                ++$created;
            } else {
                ++$updated;
            }

            $color->setPublished((bool) ($row['published'] ?? false));
            $color->setColorType($this->nullableString($row['color_type'] ?? null));
            $color->setGenericColor($this->nullableString($row['generic_color'] ?? null));
            $color->setCode($this->nullableString($row['code'] ?? null));
            $color->setName($this->nullableString($row['name'] ?? null));
            $color->setDescription($this->nullableString($row['description'] ?? null));
            $color->setAlternateCodes($this->nullableString($row['alternate_codes'] ?? null));
            $color->setImage($this->importImage($row['image'] ?? null));
            $color->save();
            $imported[$fullPath] = $color;
        }

        foreach ($payload['colors'] as $row) {
            $fullPath = $this->requiredPath($row['full_path'] ?? null);
            $color = $imported[$fullPath];
            $relations = [];
            foreach (($row['multi_color_paths'] ?? []) as $relatedPath) {
                $relatedPath = $this->requiredPath($relatedPath);
                $related = $imported[$relatedPath] ?? Color::getByPath($relatedPath);
                if (!$related instanceof Color) {
                    throw new RuntimeException(sprintf('Related color "%s" is missing.', $relatedPath));
                }
                $relations[] = $related;
            }
            $color->setMultiColor($relations);
            $color->save();
        }

        $deleted = 0;
        if ($deleteMissing) {
            $listing = new ColorListing();
            $listing->setUnpublished(true);
            foreach ($listing as $color) {
                if (!isset($imported[$color->getFullPath()])) {
                    $color->delete();
                    ++$deleted;
                }
            }
        }

        $io->success(sprintf(
            'Imported %d colors (%d updated, %d created, %d deleted).',
            count($imported),
            $updated,
            $created,
            $deleted,
        ));

        return Command::SUCCESS;
    }

    private function importImage(mixed $row): ?Image
    {
        if ($row === null) {
            return null;
        }
        if (!is_array($row)) {
            throw new RuntimeException('A color contains invalid image data.');
        }

        $fullPath = $this->requiredPath($row['full_path'] ?? null);
        $data = base64_decode((string) ($row['data'] ?? ''), true);
        if ($data === false) {
            throw new RuntimeException(sprintf('Image "%s" contains invalid base64 data.', $fullPath));
        }

        $asset = Asset::getByPath($fullPath);
        if ($asset !== null && !$asset instanceof Image) {
            throw new RuntimeException(sprintf('Asset path "%s" is not an image.', $fullPath));
        }
        if (!$asset instanceof Image) {
            $parent = AssetService::createFolderByPath(dirname($fullPath));
            if (!$parent instanceof AssetFolder) {
                throw new RuntimeException(sprintf('Unable to create asset folder for "%s".', $fullPath));
            }
            $asset = new Image();
            $asset->setParent($parent);
            $asset->setFilename(basename($fullPath));
        }
        $asset->setData($data);
        $mimeType = $this->nullableString($row['mime_type'] ?? null);
        if ($mimeType !== null) {
            $asset->setMimeType($mimeType);
        }
        $asset->save();

        return $asset;
    }

    private function requiredPath(mixed $value): string
    {
        $path = trim((string) $value);
        if ($path === '' || !str_starts_with($path, '/')) {
            throw new RuntimeException('The transfer file contains an invalid path.');
        }

        return $path;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
