<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AutomaticImageLinkingMessage;
use App\Service\AutomaticImageLinker;
use App\Service\AutomaticImageLinkingJobStore;
use Pimcore\Model\Asset;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AutomaticImageLinkingMessageHandler
{
    public function __construct(
        private AutomaticImageLinker $imageLinker,
        private AutomaticImageLinkingJobStore $jobStore,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AutomaticImageLinkingMessage $message): void
    {
        $this->jobStore->writeStatus($message->jobId, [
            'status' => 'running',
            'started_at' => gmdate(DATE_ATOM),
        ]);

        try {
            $folder = Asset::getById($message->folderId);
            if (!$folder instanceof Asset\Folder) {
                throw new RuntimeException('The image linking folder no longer exists.');
            }

            $user = User::getById($message->userId);
            $result = $this->imageLinker->processFolder($folder, $user, true);
            $result['message'] = sprintf(
                'Processed %d image(s): %d linked, %d orphan, %d error(s)',
                count($result['processed']),
                count($result['linked']),
                count($result['orphan']),
                count($result['errors']),
            );
            $this->jobStore->writeResult($message->jobId, $result);
            $this->jobStore->writeStatus($message->jobId, [
                'status' => 'completed',
                'finished_at' => gmdate(DATE_ATOM),
                'processed_count' => count($result['processed']),
                'linked_count' => count($result['linked']),
                'orphan_count' => count($result['orphan']),
                'error_count' => count($result['errors']),
            ]);
        } catch (\Throwable $exception) {
            $this->jobStore->writeStatus($message->jobId, [
                'status' => 'failed',
                'finished_at' => gmdate(DATE_ATOM),
                'error' => $exception->getMessage(),
            ]);
            $this->logger->error('Automatic image linking job failed.', [
                'job_id' => $message->jobId,
                'folder_id' => $message->folderId,
                'exception' => $exception,
            ]);
        }
    }
}
