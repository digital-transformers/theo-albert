<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ModelFrameGenerator;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Model as ModelObject;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/model-frame-generator')]
final class ModelFrameGeneratorController extends AbstractController
{
    public function __construct(
        private readonly ModelFrameGenerator $frameGenerator,
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    #[Route('/generate/{id}', name: 'admin_model_frame_generator_generate', methods: ['POST'])]
    public function generate(Request $request, int $id): JsonResponse
    {
        $model = DataObject::getById($id, ['force' => true]);
        if (!$model instanceof ModelObject || $model->getClassName() !== 'model') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Object is not a model data object',
            ], 404);
        }

        $user = $this->userResolver->getUser();
        if (!$user || !$model->isAllowed('create', $user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to create frame children for this model',
            ], 403);
        }

        $frameClass = ClassDefinition::getByName('frame');
        if ($frameClass !== null && !$user->isAdmin() && !$user->isAllowed($frameClass->getId(), 'class')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to create frame objects',
            ], 403);
        }

        $submittedDetails = $this->extractSubmittedDetails($request);
        $result = $this->frameGenerator->generate($model, $submittedDetails, $user);

        return new JsonResponse([
            'success' => $result['errors'] === [],
            'message' => $this->buildMessage($result),
            ...$result,
        ], $result['errors'] === [] ? 200 : 207);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function extractSubmittedDetails(Request $request): ?array
    {
        $rawData = (string) $request->request->get('data', '');
        if ($rawData === '') {
            return null;
        }

        try {
            $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data) || !array_key_exists('finalProductDetails', $data) || !is_array($data['finalProductDetails'])) {
            return null;
        }

        return array_values($data['finalProductDetails']);
    }

    /**
     * @param array{created: list<array{id: int, code: string, path: string}>, skipped: list<array{code: string, reason: string}>, errors: list<string>} $result
     */
    private function buildMessage(array $result): string
    {
        return sprintf(
            'Created %d frame(s), skipped %d, errors %d',
            count($result['created']),
            count($result['skipped']),
            count($result['errors'])
        );
    }
}
