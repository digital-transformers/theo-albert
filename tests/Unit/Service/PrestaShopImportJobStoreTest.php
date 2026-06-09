<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PrestaShopImportJobStore;
use Codeception\Test\Unit;

final class PrestaShopImportJobStoreTest extends Unit
{
    private string $jobsDirectory;

    protected function _before(): void
    {
        $this->jobsDirectory = sys_get_temp_dir() . '/prestashop-import-jobs-' . bin2hex(random_bytes(6));
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->jobsDirectory);
    }

    public function testStatusUpdatesPreserveMetadataAndJobsAreListedNewestFirst(): void
    {
        $store = new PrestaShopImportJobStore($this->jobsDirectory);
        $olderId = '019eab72-8c5c-7bd6-932b-fde543fd5fa7';
        $newerId = '019eab72-e6f1-76f1-a8a1-0c4f1758bf8e';

        $store->createJob($olderId, "PK older", 'older-export.zip');
        $store->writeStatus($olderId, ['status' => 'completed', 'created_at' => '2026-06-09T08:00:00+00:00']);
        $store->createJob($newerId, "PK newer", 'newer-export.zip');
        $store->writeStatus($newerId, [
            'status' => 'syncing',
            'created_at' => '2026-06-09T09:00:00+00:00',
            'stage' => 'frames',
        ]);

        $status = $store->readStatus($newerId);
        self::assertSame('newer-export.zip', $status['filename']);
        self::assertSame('syncing', $status['status']);
        self::assertSame('frames', $status['stage']);

        $jobs = $store->listJobs();
        self::assertSame([$newerId, $olderId], array_column($jobs, 'job_id'));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $path = $directory . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
