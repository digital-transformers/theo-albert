<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\CommercialPricingGenerator;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commercial-pricing-generator')]
final class CommercialPricingGeneratorController extends AbstractController
{
    public function __construct(
        private readonly CommercialPricingGenerator $generator,
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    #[Route('/generate/{id}', name: 'admin_commercial_pricing_generator_generate', methods: ['POST'])]
    public function generate(Request $request, int $id): JsonResponse
    {
        $object = DataObject::getById($id, ['force' => true]);
        if (!$object instanceof Family && !$object instanceof Frame) {
            return new JsonResponse(['success' => false, 'message' => 'Object is not a Family or Frame.'], 404);
        }

        $user = $this->userResolver->getUser();
        if (!$user || !$object->isAllowed('save', $user)) {
            return new JsonResponse(['success' => false, 'message' => 'Missing permission to generate pricing.'], 403);
        }

        $result = $this->generator->generate($object, $this->extractBasePrice($request), $user);
        $message = sprintf(
            'Updated %d frame(s) with %d commercial pricelist price(s).',
            count($result['updated']),
            $result['pricelistCount']
        );

        if ($result['errors'] !== []) {
            $message .= ' ' . implode(' ', $result['errors']);
        }

        return new JsonResponse([
            'success' => $result['errors'] === [],
            'message' => $message,
            ...$result,
        ], $result['errors'] === [] ? 200 : 422);
    }

    private function extractBasePrice(Request $request): ?int
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

        if (!is_array($data) || !array_key_exists('basePrice', $data)) {
            return null;
        }

        $value = filter_var($data['basePrice'], FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }
}
