<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AutomaticImageLinker;
use Pimcore\Model\Asset;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/automatic-image-linking')]
final class AutomaticImageLinkingController extends AbstractController
{
    public function __construct(
        private readonly AutomaticImageLinker $imageLinker,
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
        if (!$user || !$folder->isAllowed('view', $user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to process this asset folder',
            ], 403);
        }

        $result = $this->imageLinker->processFolder($folder, $user, true);

        return new JsonResponse([
            'success' => $result['errors'] === [],
            'message' => sprintf(
                'Processed %d image(s): %d linked, %d orphan, %d error(s)',
                count($result['processed']),
                count($result['linked']),
                count($result['orphan']),
                count($result['errors'])
            ),
            ...$result,
        ], $result['errors'] === [] ? 200 : 207);
    }
}
