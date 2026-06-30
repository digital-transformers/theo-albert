<?php
declare(strict_types=1);

namespace App\Service;

use Pimcore\Tool\Console as PimcoreConsole;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Uid\Uuid;

final class PrestaShopImportLauncher
{
    public const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

    public function __construct(private readonly PrestaShopImportJobStore $jobStore)
    {
    }

    /**
     * @return array{job_id: string, pid: int}
     */
    public function enqueue(
        string $contents,
        string $filename = 'export.zip',
        ?int $modelLimit = null,
        string $models = '',
    ): array
    {
        if (!str_starts_with($contents, "PK")) {
            throw new RuntimeException('A valid ZIP export is required.');
        }
        if (strlen($contents) > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('The ZIP export exceeds the 50 MB limit.');
        }

        $jobId = Uuid::v7()->toRfc4122();
        $inputPath = $this->jobStore->createJob($jobId, $contents, $filename);
        $this->jobStore->writeStatus($jobId, [
            'selection' => [
                'model_limit' => $modelLimit,
                'models' => trim($models),
            ],
        ]);
        $logPath = $this->jobStore->jobDirectory($jobId) . '/worker.log';
        $command = [
            PimcoreConsole::getPhpCli() ?: '/usr/bin/php',
            \PIMCORE_PROJECT_ROOT . '/bin/console',
            'app:prestashop-export:sync',
            $inputPath,
            '--job-id=' . $jobId,
            '--no-interaction',
            '--no-ansi',
        ];
        if ($modelLimit !== null) {
            if ($modelLimit < 1) {
                throw new RuntimeException('Model limit must be a positive integer.');
            }
            $command[] = '--model-limit=' . $modelLimit;
        }
        if (trim($models) !== '') {
            $command[] = '--models=' . trim($models);
        }
        $shell = sprintf(
            'nohup %s > %s 2>&1 & echo $!',
            implode(' ', array_map('escapeshellarg', $command)),
            escapeshellarg($logPath)
        );
        $process = Process::fromShellCommandline($shell, \PIMCORE_PROJECT_ROOT);
        $process->run();
        $pid = (int) trim($process->getOutput());
        if (!$process->isSuccessful() || $pid <= 0) {
            $this->jobStore->writeStatus($jobId, [
                'status' => 'failed',
                'filename' => $filename,
                'error' => 'Unable to start import worker.',
            ]);

            throw new RuntimeException('Unable to start import worker.');
        }

        return ['job_id' => $jobId, 'pid' => $pid];
    }
}
