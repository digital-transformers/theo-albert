<?php
declare(strict_types=1);

namespace App\Command;

use JsonException;
use Pimcore\Model\DataObject\SAPPricelist;
use Pimcore\Model\DataObject\SAPPricelist\Listing as SAPPricelistListing;
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
    name: 'app:sap-pricelists:transfer',
    description: 'Export or import SAP pricelists and their base-pricelist relationships.',
)]
final class TransferSAPPricelistsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'export or import')
            ->addArgument('file', InputArgument::REQUIRED, 'JSON transfer file')
            ->addOption('delete-missing', null, InputOption::VALUE_NONE, 'Delete destination pricelists absent from the transfer file');
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
        $listing = new SAPPricelistListing();
        $listing->setUnpublished(true);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        $pricelists = [];
        foreach ($listing as $pricelist) {
            $basePricelist = $pricelist->getBasePricelist();
            $pricelists[] = [
                'source_id' => $pricelist->getId(),
                'full_path' => $pricelist->getFullPath(),
                'published' => $pricelist->getPublished(),
                'code' => $pricelist->getCode(),
                'name' => $pricelist->getName(),
                'description' => $pricelist->getDescription(),
                'currency' => $pricelist->getCurrency(),
                'base_factor' => $pricelist->getBaseFactor(),
                'commercial_pricelist' => $pricelist->getCommercialPricelist(),
                'rounding' => $pricelist->getRounding(),
                'base_pricelist_path' => $basePricelist instanceof SAPPricelist
                    ? $basePricelist->getFullPath()
                    : null,
            ];
        }

        $directory = dirname($file);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }
        $payload = json_encode([
            'format' => 'theo-sap-pricelist-transfer-v1',
            'exported_at' => date(DATE_ATOM),
            'pricelists' => $pricelists,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($file, $payload . "\n") === false) {
            throw new RuntimeException(sprintf('Unable to write "%s".', $file));
        }

        $io->success(sprintf('Exported %d SAP pricelists to %s.', count($pricelists), $file));

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
        if (($payload['format'] ?? null) !== 'theo-sap-pricelist-transfer-v1' || !is_array($payload['pricelists'] ?? null)) {
            throw new RuntimeException('The transfer file has an unsupported format.');
        }

        $sourcePaths = [];
        $sourceCodes = [];
        foreach ($payload['pricelists'] as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('The transfer file contains an invalid pricelist row.');
            }
            $path = $this->requiredPath($row['full_path'] ?? null);
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                throw new RuntimeException(sprintf('Pricelist "%s" has no code.', $path));
            }
            if (isset($sourcePaths[$path]) || isset($sourceCodes[$code])) {
                throw new RuntimeException(sprintf('Duplicate pricelist path or code detected for "%s".', $code));
            }
            $sourcePaths[$path] = true;
            $sourceCodes[$code] = true;
        }

        $existing = new SAPPricelistListing();
        $existing->setUnpublished(true);
        $existingObjects = iterator_to_array($existing);
        $byCode = [];
        $byPath = [];
        foreach ($existingObjects as $pricelist) {
            $byCode[(string) $pricelist->getCode()] = $pricelist;
            $byPath[$pricelist->getFullPath()] = $pricelist;
        }

        /** @var array<string, SAPPricelist> $mapped */
        $mapped = [];
        $usedIds = [];
        foreach ($payload['pricelists'] as $row) {
            $path = $this->requiredPath($row['full_path'] ?? null);
            $code = (string) $row['code'];
            $pricelist = $byCode[$code] ?? $byPath[$path] ?? null;
            if ($pricelist instanceof SAPPricelist && isset($usedIds[(int) $pricelist->getId()])) {
                $pricelist = null;
            }
            if (!$pricelist instanceof SAPPricelist) {
                $pricelist = new SAPPricelist();
            }
            $mapped[$path] = $pricelist;
            if ($pricelist->getId()) {
                $usedIds[(int) $pricelist->getId()] = true;
            }
        }

        $deleted = 0;
        if ($deleteMissing) {
            foreach ($existingObjects as $pricelist) {
                if (!isset($usedIds[(int) $pricelist->getId()])) {
                    $pricelist->delete();
                    ++$deleted;
                }
            }
        }

        foreach ($mapped as $pricelist) {
            if ($pricelist->getId()) {
                $pricelist->setCode(sprintf('__pricelist_transfer_%d__', $pricelist->getId()));
                $pricelist->setBasePricelist(null);
                $pricelist->save();
            }
        }

        $created = 0;
        $updated = 0;
        foreach ($payload['pricelists'] as $row) {
            $path = $this->requiredPath($row['full_path'] ?? null);
            $pricelist = $mapped[$path];
            $isNew = !$pricelist->getId();
            $pricelist->setParent(DataObjectService::createFolderByPath(dirname($path)));
            $pricelist->setKey(basename($path));
            $pricelist->setPublished((bool) ($row['published'] ?? false));
            $pricelist->setCode($this->nullableString($row['code'] ?? null));
            $pricelist->setName($this->nullableString($row['name'] ?? null));
            $pricelist->setDescription($this->nullableString($row['description'] ?? null));
            $pricelist->setCurrency($this->nullableString($row['currency'] ?? null));
            $pricelist->setBaseFactor($this->nullableFloat($row['base_factor'] ?? null));
            $pricelist->setCommercialPricelist($this->nullableBool($row['commercial_pricelist'] ?? null));
            $pricelist->setRounding($this->nullableString($row['rounding'] ?? null));
            $pricelist->save();
            $isNew ? ++$created : ++$updated;
        }

        foreach ($payload['pricelists'] as $row) {
            $path = $this->requiredPath($row['full_path'] ?? null);
            $basePath = $row['base_pricelist_path'] ?? null;
            $base = $basePath === null ? null : ($mapped[$this->requiredPath($basePath)] ?? null);
            if ($basePath !== null && !$base instanceof SAPPricelist) {
                throw new RuntimeException(sprintf('Base pricelist "%s" is missing.', (string) $basePath));
            }
            $mapped[$path]->setBasePricelist($base);
            $mapped[$path]->save();
        }

        $io->success(sprintf(
            'Imported %d SAP pricelists (%d updated, %d created, %d deleted).',
            count($mapped),
            $updated,
            $created,
            $deleted,
        ));

        return Command::SUCCESS;
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

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
