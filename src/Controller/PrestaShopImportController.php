<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PrestaShopImportJobStore;
use App\Service\PrestaShopImportLauncher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/prestashop')]
final class PrestaShopImportController extends AbstractController
{
    public function __construct(
        private readonly PrestaShopImportJobStore $jobStore,
        private readonly PrestaShopImportLauncher $launcher,
        private readonly string $importToken,
    ) {
    }

    #[Route('/import', name: 'api_prestashop_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        [$contents, $filename] = $this->readZipContents($request);
        if ($contents === null) {
            return new JsonResponse(['success' => false, 'message' => 'A valid ZIP export is required.'], 400);
        }

        try {
            $modelLimitValue = trim((string) $request->request->get('modelLimit', $request->query->get('modelLimit', '')));
            $modelLimit = $modelLimitValue === '' ? null : filter_var($modelLimitValue, FILTER_VALIDATE_INT);
            if ($modelLimitValue !== '' && ($modelLimit === false || $modelLimit < 1)) {
                return new JsonResponse(['success' => false, 'message' => 'Model limit must be a positive integer.'], 400);
            }
            $job = $this->launcher->enqueue(
                $contents,
                $filename,
                $modelLimit,
                (string) $request->request->get('models', $request->query->get('models', ''))
            );
        } catch (\RuntimeException $exception) {
            $status = str_contains($exception->getMessage(), '50 MB') ? 413 : 400;
            if (str_contains($exception->getMessage(), 'worker')) {
                $status = 500;
            }

            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()], $status);
        }
        $jobId = $job['job_id'];
        return new JsonResponse([
            'success' => true,
            'job_id' => $jobId,
            'status' => 'queued',
            'status_url' => $this->generateUrl('api_prestashop_import_status', ['jobId' => $jobId]),
            'report_url' => $this->generateUrl('api_prestashop_import_report', ['jobId' => $jobId]),
        ], 202);
    }

    #[Route('/import/{jobId}', name: 'api_prestashop_import_status', methods: ['GET'])]
    public function status(Request $request, string $jobId): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $status = $this->jobStore->readStatus($jobId);
        } catch (\Throwable) {
            $status = null;
        }
        if ($status === null) {
            return new JsonResponse(['success' => false, 'message' => 'Import job not found.'], 404);
        }

        return new JsonResponse(['success' => true, ...$status]);
    }

    #[Route('/import/{jobId}/report', name: 'api_prestashop_import_report', methods: ['GET'])]
    public function report(Request $request, string $jobId): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $report = $this->jobStore->readReport($jobId);
        } catch (\Throwable) {
            $report = null;
        }
        if ($report === null) {
            return new JsonResponse(['success' => false, 'message' => 'Import report not available.'], 404);
        }

        return new JsonResponse(['success' => true, 'job_id' => $jobId, 'report' => $report]);
    }

    private function isAuthorized(Request $request): bool
    {
        $provided = trim((string) $request->headers->get('X-PrestaShop-Token', ''));
        if ($provided === '') {
            $authorization = trim((string) $request->headers->get('Authorization', ''));
            $provided = str_starts_with($authorization, 'Bearer ') ? substr($authorization, 7) : '';
        }

        return $this->importToken !== '' && $provided !== '' && hash_equals($this->importToken, $provided);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function readZipContents(Request $request): array
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $contents = file_get_contents($file->getPathname());

            return [is_string($contents) ? $contents : null, $file->getClientOriginalName()];
        }

        $contents = $request->getContent();

        return [$contents !== '' ? $contents : null, 'export.zip'];
    }
}
