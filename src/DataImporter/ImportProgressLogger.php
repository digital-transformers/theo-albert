<?php declare(strict_types=1);

namespace App\DataImporter;

use Psr\Log\LoggerInterface;

final class ImportProgressLogger
{
    public function __construct(private LoggerInterface $logger) {}

    public function onBatchProgress(int $processed, int $total, int $failed = 0): void
    {
        $this->logger->info(sprintf('[progress] processed=%d total=%d failed=%d', $processed, $total, $failed));
    }
}
