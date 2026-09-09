<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\EventSubscriber\ProductPermissionSubscriber;
use App\Message\AutomaticImageLinkingMessage;
use App\Service\AutomaticImageLinkingJobStore;
use Pimcore\Model\Asset;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/admin/automatic-image-linking')]
final class AutomaticImageLinkingController extends AbstractController
{
    public function __construct(
        private readonly AutomaticImageLinkingJobStore $jobStore,
        private readonly MessageBusInterface $messageBus,
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    #[Route('/process-folder/{id}', name: 'admin_automatic_image_linking_process_folder', methods: ['POST'])]
    public function processFolder(int $id): JsonResponse
    {
        $folder = Asset::getById($id);
        if (!$folder instanceof Asset\Folder) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Asset is not a folder',
            ], 404);
        }

        $user = $this->userResolver->getUser();
        $canProcessFolder = $user
            && $folder->isAllowed('view', $user)
            && ($user->isAdmin() || $user->isAllowed(ProductPermissionSubscriber::PERMISSION_AUTOMATIC_IMAGE_LINKING));

        if (!$canProcessFolder) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to process this asset folder',
            ], 403);
        }

        $jobId = Uuid::v7()->toRfc4122();
        $this->jobStore->createJob($jobId, $folder, $user);
        $this->messageBus->dispatch(new AutomaticImageLinkingMessage(
            $jobId,
            (int) $folder->getId(),
            (int) $user->getId(),
        ));

        return new JsonResponse([
            'success' => true,
            'status' => 'queued',
            'job_id' => $jobId,
            'message' => 'Image linking queued.',
            'status_url' => $this->generateUrl('admin_automatic_image_linking_status', ['jobId' => $jobId]),
        ], 202);
    }

    #[Route('/status/{jobId}', name: 'admin_automatic_image_linking_status', methods: ['GET'])]
    public function status(string $jobId): JsonResponse
    {
        try {
            $status = $this->jobStore->readStatus($jobId);
        } catch (\Throwable) {
            $status = null;
        }
        if ($status === null) {
            return new JsonResponse(['success' => false, 'message' => 'Image linking job not found.'], 404);
        }

        $folder = Asset::getById((int) ($status['folder_id'] ?? 0));
        $user = $this->userResolver->getUser();
        $canView = $folder instanceof Asset\Folder
            && $user
            && $folder->isAllowed('view', $user)
            && ($user->isAdmin() || $user->isAllowed(ProductPermissionSubscriber::PERMISSION_AUTOMATIC_IMAGE_LINKING));
        if (!$canView) {
            return new JsonResponse(['success' => false, 'message' => 'Missing permission to view this job.'], 403);
        }

        $response = ['success' => true, ...$status];
        if (($status['status'] ?? null) === 'completed') {
            $response['result'] = $this->jobStore->readResult($jobId);
        }

        return new JsonResponse($response);
    }
}
