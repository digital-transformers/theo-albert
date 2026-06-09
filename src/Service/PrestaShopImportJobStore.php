<?php
declare(strict_types=1);

namespace App\Service;

use JsonException;
use RuntimeException;

final class PrestaShopImportJobStore
{
    public function __construct(private readonly string $jobsDirectory)
    {
    }

    public function createJob(string $jobId, string $zipContents, string $filename = 'export.zip'): string
    {
        $directory = $this->jobDirectory($jobId);
        if (!mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create import job directory.');
        }
        $inputPath = $directory . '/export.zip';
        if (file_put_contents($inputPath, $zipContents) === false) {
            throw new RuntimeException('Unable to store import ZIP.');
        }

        $this->writeStatus($jobId, [
            'status' => 'queued',
            'filename' => basename($filename) ?: 'export.zip',
            'created_at' => gmdate(DATE_ATOM),
        ]);

        return $inputPath;
    }

    /**
     * @param array<string, mixed> $status
     */
    public function writeStatus(string $jobId, array $status): void
    {
        $path = $this->jobDirectory($jobId) . '/status.json';
        $status = [
            ...($this->readJson($path) ?? []),
            ...$status,
            'job_id' => $jobId,
            'updated_at' => gmdate(DATE_ATOM),
        ];
        $this->writeJson($path, $status);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readStatus(string $jobId): ?array
    {
        return $this->readJson($this->jobDirectory($jobId) . '/status.json');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readReport(string $jobId): ?array
    {
        return $this->readJson($this->jobDirectory($jobId) . '/report.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listJobs(int $limit = 100): array
    {
        if (!is_dir($this->jobsDirectory)) {
            return [];
        }

        $jobs = [];
        foreach (scandir($this->jobsDirectory, SCANDIR_SORT_DESCENDING) ?: [] as $jobId) {
            if (!preg_match('/^[a-f0-9-]{36}$/', $jobId)) {
                continue;
            }

            $status = $this->readStatus($jobId);
            if ($status !== null) {
                $jobs[] = $status;
            }
            if (count($jobs) >= max(1, min(500, $limit))) {
                break;
            }
        }

        usort(
            $jobs,
            static fn (array $left, array $right): int =>
                strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
        );

        return $jobs;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    public function jobDirectory(string $jobId): string
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $jobId)) {
            throw new RuntimeException('Invalid import job ID.');
        }

        return rtrim($this->jobsDirectory, '/') . '/' . $jobId;
    }

    public function lockPath(): string
    {
        if (!is_dir($this->jobsDirectory) && !mkdir($this->jobsDirectory, 0770, true) && !is_dir($this->jobsDirectory)) {
            throw new RuntimeException('Unable to create import jobs directory.');
        }

        return rtrim($this->jobsDirectory, '/') . '/import.lock';
    }

    /**
     * @param array<string, mixed> $value
     */
    public function writeReport(string $jobId, array $value): void
    {
        $this->writeJson($this->jobDirectory($jobId) . '/report.json', $value);
    }

    /**
     * @param array<string, mixed> $value
     */
    private function writeJson(string $path, array $value): void
    {
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $temporary = $path . '.tmp';
        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $path)) {
            throw new RuntimeException('Unable to write import job state.');
        }
    }
}
