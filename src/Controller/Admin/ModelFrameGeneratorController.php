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
        if (!$user || !$model->isAllowed('create', $user) || !$model->isAllowed('save', $user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to create frame children or update finalProducts for this model',
            ], 403);
        }

        $frameClass = ClassDefinition::getByName('frame');
        if ($frameClass !== null && !$user->isAdmin() && !$user->isAllowed($frameClass->getId(), 'class')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing permission to create frame objects',
            ], 403);
        }

        $submittedData = $this->extractSubmittedData($request);
        $submittedDetails = $this->extractSubmittedDetails($submittedData);
        $submittedFinalProducts = $this->extractSubmittedFinalProducts($submittedData);
        $submittedModelBaseData = $this->extractSubmittedModelBaseData($submittedData);
        $result = $this->frameGenerator->generate(
            $model,
            $submittedDetails,
            $user,
            $submittedFinalProducts,
            $submittedModelBaseData
        );

        return new JsonResponse([
            'success' => $result['errors'] === [],
            'message' => $this->buildMessage($result),
            ...$result,
        ], $result['errors'] === [] ? 200 : 207);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractSubmittedData(Request $request): ?array
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

        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return list<array<string, mixed>>|null
     */
    private function extractSubmittedDetails(?array $data): ?array
    {
        if ($data === null || !array_key_exists('finalProductDetails', $data) || !is_array($data['finalProductDetails'])) {
            return null;
        }

        return array_values($data['finalProductDetails']);
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return list<array<string, mixed>>|null
     */
    private function extractSubmittedFinalProducts(?array $data): ?array
    {
        if ($data === null || !array_key_exists('finalProducts', $data) || !is_array($data['finalProducts'])) {
            return null;
        }

        return array_values($data['finalProducts']);
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array{frameBaseCode?: mixed, name?: mixed}|null
     */
    private function extractSubmittedModelBaseData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $baseData = [];
        if (array_key_exists('frameBaseCode', $data)) {
            $baseData['frameBaseCode'] = $data['frameBaseCode'];
        }
        if (array_key_exists('name', $data)) {
            $baseData['name'] = $data['name'];
        }

        return $baseData === [] ? null : $baseData;
    }

    /**
     * @param array{created: list<array{id: int, code: string, name: string, path: string}>, skipped: list<array{code: string, reason: string}>, errors: list<string>} $result
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
