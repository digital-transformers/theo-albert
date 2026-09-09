<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AutomaticImageLinkingJobStore;
use Codeception\Test\Unit;
use RuntimeException;

final class AutomaticImageLinkingJobStoreTest extends Unit
{
    private string $jobsDirectory;

    protected function _before(): void
    {
        $this->jobsDirectory = sys_get_temp_dir() . '/automatic-image-linking-' . bin2hex(random_bytes(8));
    }

    protected function _after(): void
    {
        if (!is_dir($this->jobsDirectory)) {
            return;
        }

        foreach (glob($this->jobsDirectory . '/*/*') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($this->jobsDirectory . '/*') ?: [] as $directory) {
            rmdir($directory);
        }
        rmdir($this->jobsDirectory);
    }

    public function testStatusUpdatesAreMergedAndResultIsStored(): void
    {
        $jobId = '0199aaaa-bbbb-7ccc-8ddd-eeeeeeeeeeee';
        $store = new AutomaticImageLinkingJobStore($this->jobsDirectory);

        $store->writeStatus($jobId, ['status' => 'queued', 'folder_id' => 6]);
        $store->writeStatus($jobId, ['status' => 'running']);
        $store->writeResult($jobId, ['processed' => [['id' => 42]], 'errors' => []]);

        $status = $store->readStatus($jobId);
        self::assertSame('running', $status['status']);
        self::assertSame(6, $status['folder_id']);
        self::assertSame($jobId, $status['job_id']);
        self::assertArrayHasKey('updated_at', $status);
        self::assertSame(42, $store->readResult($jobId)['processed'][0]['id']);
    }

    public function testInvalidJobIdIsRejected(): void
    {
        $this->expectException(RuntimeException::class);

        (new AutomaticImageLinkingJobStore($this->jobsDirectory))->readStatus('../invalid');
    }
}
