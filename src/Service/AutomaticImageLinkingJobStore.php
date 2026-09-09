<?php
declare(strict_types=1);

namespace App\Service;

use JsonException;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\User;
use RuntimeException;

final readonly class AutomaticImageLinkingJobStore
{
    public function __construct(private string $jobsDirectory)
    {
    }

    public function createJob(string $jobId, Folder $folder, User $user): void
    {
        $directory = $this->jobDirectory($jobId);
        if (!mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create image linking job directory.');
        }

        $this->writeStatus($jobId, [
            'status' => 'queued',
            'folder_id' => (int) $folder->getId(),
            'folder_path' => $folder->getRealFullPath(),
            'user_id' => (int) $user->getId(),
            'created_at' => gmdate(DATE_ATOM),
        ]);
    }

    /** @param array<string, mixed> $status */
    public function writeStatus(string $jobId, array $status): void
    {
        $path = $this->jobDirectory($jobId) . '/status.json';
        $this->writeJson($path, [
            ...($this->readJson($path) ?? []),
            ...$status,
            'job_id' => $jobId,
            'updated_at' => gmdate(DATE_ATOM),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function readStatus(string $jobId): ?array
    {
        return $this->readJson($this->jobDirectory($jobId) . '/status.json');
    }

    /** @param array<string, mixed> $result */
    public function writeResult(string $jobId, array $result): void
    {
        $this->writeJson($this->jobDirectory($jobId) . '/result.json', $result);
    }

    /** @return array<string, mixed>|null */
    public function readResult(string $jobId): ?array
    {
        return $this->readJson($this->jobDirectory($jobId) . '/result.json');
    }

    /** @return list<array<string, mixed>> */
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
        }

        usort(
            $jobs,
            static fn (array $left, array $right): int =>
                strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')),
        );

        return array_slice($jobs, 0, max(1, min(500, $limit)));
    }

    public function jobDirectory(string $jobId): string
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $jobId)) {
            throw new RuntimeException('Invalid image linking job ID.');
        }

        return rtrim($this->jobsDirectory, '/') . '/' . $jobId;
    }

    /** @return array<string, mixed>|null */
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

    /** @param array<string, mixed> $value */
    private function writeJson(string $path, array $value): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create image linking job directory.');
        }
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $temporary = $path . '.tmp';
        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $path)) {
            throw new RuntimeException('Unable to write image linking job state.');
        }
    }
}
