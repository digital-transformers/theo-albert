<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\PrestaShopExportConverter;
use App\Service\PrestaShopImportJobStore;
use App\Service\ProductHierarchySyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:prestashop-export:sync',
    description: 'Convert a PrestaShop export and upsert it through ProductHierarchy GraphQL.',
)]
final class SyncPrestaShopExportCommand extends Command
{
    public function __construct(
        private readonly PrestaShopExportConverter $converter,
        private readonly ProductHierarchySyncService $syncService,
        private readonly PrestaShopImportJobStore $jobStore,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'Path to export ZIP')
            ->addOption('job-id', null, InputOption::VALUE_REQUIRED, 'Import job ID')
            ->addOption('parent-path', null, InputOption::VALUE_REQUIRED, 'Family root path', '/Product Data/Families')
            ->addOption('model-limit', null, InputOption::VALUE_REQUIRED, 'Limit import to the first N resolved models and their child frames')
            ->addOption('models', null, InputOption::VALUE_REQUIRED, 'Comma-separated model codes or exact names to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobId = (string) ($input->getOption('job-id') ?: Uuid::v7()->toRfc4122());
        $lock = fopen($this->jobStore->lockPath(), 'c+');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->jobStore->writeStatus($jobId, ['status' => 'failed', 'error' => 'Another import is already running.']);

            return Command::FAILURE;
        }

        $finished = false;
        $memoryReserve = str_repeat('x', 1024 * 1024);
        register_shutdown_function(function () use ($jobId, &$finished, &$memoryReserve): void {
            $memoryReserve = '';
            $error = error_get_last();
            if ($finished || !is_array($error) || !in_array($error['type'] ?? null, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            try {
                $this->jobStore->writeStatus($jobId, [
                    'status' => 'failed',
                    'error' => 'Import worker terminated: ' . (string) ($error['message'] ?? 'fatal error'),
                    'completed_at' => gmdate(DATE_ATOM),
                ]);
            } catch (\Throwable) {
            }
        });

        try {
            $this->jobStore->writeStatus($jobId, [
                'status' => 'converting',
                'stage' => 'reading export',
                'started_at' => gmdate(DATE_ATOM),
            ]);
            $converted = $this->converter->convert(
                (string) $input->getArgument('input'),
                (string) $input->getOption('parent-path'),
                $this->modelLimit($input),
                $this->modelFilters($input)
            );
            $this->jobStore->writeStatus($jobId, [
                'status' => 'syncing',
                'stage' => 'families',
                'conversion_summary' => $converted['report']['summary'],
            ]);

            $sync = $this->syncService->sync($converted, function (array $progress) use ($jobId, $converted): void {
                $this->jobStore->writeStatus($jobId, [
                    'status' => 'syncing',
                    'conversion_summary' => $converted['report']['summary'],
                    ...$progress,
                ]);
            });
            $report = ['conversion' => $converted['report'], 'sync' => $sync];
            $this->jobStore->writeReport($jobId, $report);

            $failed = (int) $sync['families']['failed'] + (int) $sync['models']['failed'] + (int) $sync['frames']['failed'];
            $this->jobStore->writeStatus($jobId, [
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
                'stage' => 'completed',
                'completed_at' => gmdate(DATE_ATOM),
                'conversion_summary' => $converted['report']['summary'],
                'sync' => [
                    'families' => $sync['families'],
                    'models' => $sync['models'],
                    'frames' => $sync['frames'],
                    'error_count' => count($sync['errors']),
                ],
            ]);
            $finished = true;

            return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Throwable $exception) {
            $this->jobStore->writeStatus($jobId, [
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
            $finished = true;
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function modelLimit(InputInterface $input): ?int
    {
        $value = $input->getOption('model-limit');
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            throw new \InvalidArgumentException('--model-limit must be a positive integer.');
        }

        return (int) $value;
    }

    /**
     * @return list<string>
     */
    private function modelFilters(InputInterface $input): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', (string) $input->getOption('models'))),
            static fn (string $value): bool => $value !== ''
        ));
    }
}
