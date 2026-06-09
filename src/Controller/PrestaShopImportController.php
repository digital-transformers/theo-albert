<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PrestaShopImportJobStore;
use Pimcore\Tool\Console as PimcoreConsole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/integrations/prestashop')]
final class PrestaShopImportController extends AbstractController
{
    private const MAX_UPLOAD_BYTES = 50 * 1024 * 1024;

    public function __construct(
        private readonly PrestaShopImportJobStore $jobStore,
        private readonly string $importToken,
    ) {
    }

    #[Route('/import', name: 'api_prestashop_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $contents = $this->readZipContents($request);
        if ($contents === null || !str_starts_with($contents, "PK")) {
            return new JsonResponse(['success' => false, 'message' => 'A valid ZIP export is required.'], 400);
        }
        if (strlen($contents) > self::MAX_UPLOAD_BYTES) {
            return new JsonResponse(['success' => false, 'message' => 'The ZIP export exceeds the 50 MB limit.'], 413);
        }

        $jobId = Uuid::v7()->toRfc4122();
        $inputPath = $this->jobStore->createJob($jobId, $contents);
        $logPath = $this->jobStore->jobDirectory($jobId) . '/worker.log';
        $command = [
            PimcoreConsole::getPhpCli() ?: '/usr/bin/php',
            \PIMCORE_PROJECT_ROOT . '/bin/console',
            'app:prestashop-export:sync',
            $inputPath,
            '--job-id=' . $jobId,
            '--no-interaction',
            '--no-ansi',
        ];
        $shell = sprintf(
            'nohup %s > %s 2>&1 & echo $!',
            implode(' ', array_map('escapeshellarg', $command)),
            escapeshellarg($logPath)
        );
        $process = Process::fromShellCommandline($shell, \PIMCORE_PROJECT_ROOT);
        $process->run();
        $pid = (int) trim($process->getOutput());
        if (!$process->isSuccessful() || $pid <= 0) {
            $this->jobStore->writeStatus($jobId, ['status' => 'failed', 'error' => 'Unable to start import worker.']);

            return new JsonResponse(['success' => false, 'job_id' => $jobId, 'message' => 'Unable to start import worker.'], 500);
        }
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

    private function readZipContents(Request $request): ?string
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $contents = file_get_contents($file->getPathname());

            return is_string($contents) ? $contents : null;
        }

        $contents = $request->getContent();

        return $contents !== '' ? $contents : null;
    }
}
