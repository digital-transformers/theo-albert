<?php
declare(strict_types=1);

namespace App\Message;

final readonly class AutomaticImageLinkingMessage
{
    public function __construct(
        public string $jobId,
        public int $folderId,
        public int $userId,
    ) {
    }
}
