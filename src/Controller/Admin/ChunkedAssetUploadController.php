<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/chunked-asset-upload')]
final class ChunkedAssetUploadController extends AbstractController
{
    private const MAX_FILE_BYTES = 1024 * 1024 * 1024;
    private const MAX_CHUNK_BYTES = 20 * 1024 * 1024;
    private const STALE_AFTER_SECONDS = 86400;

    public function __construct(
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    #[Route('/chunk', name: 'admin_chunked_asset_upload_chunk', methods: ['POST'])]
    public function chunk(Request $request): JsonResponse
    {
        try {
            $user = $this->userResolver->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('An authenticated Pimcore user is required.');
            }

            $uploadId = (string) $request->request->get('uploadId', '');
            if (!preg_match('/^[a-f0-9-]{16,64}$/i', $uploadId)) {
                throw new \InvalidArgumentException('Invalid upload identifier.');
            }

            $totalSize = filter_var($request->request->get('totalSize'), FILTER_VALIDATE_INT);
            $offset = filter_var($request->request->get('offset'), FILTER_VALIDATE_INT);
            if ($totalSize === false || $totalSize < 1 || $totalSize > self::MAX_FILE_BYTES) {
                throw new \InvalidArgumentException('The file exceeds the 1 GiB upload limit.');
            }
            if ($offset === false || $offset < 0 || $offset >= $totalSize) {
                throw new \InvalidArgumentException('Invalid chunk offset.');
            }

            $chunk = $request->files->get('Filedata');
            if (!$chunk instanceof UploadedFile || !$chunk->isValid()) {
                throw new \InvalidArgumentException('The uploaded chunk is invalid.');
            }
            $chunkSize = (int) $chunk->getSize();
            if ($chunkSize < 1 || $chunkSize > self::MAX_CHUNK_BYTES || $offset + $chunkSize > $totalSize) {
                throw new \InvalidArgumentException('Invalid chunk size.');
            }

            $directory = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/chunked-asset-uploads/' . $user->getId();
            $filesystem = new Filesystem();
            $filesystem->mkdir($directory, 0700);
            $this->removeStaleUploads($directory, $filesystem);

            $partPath = $directory . '/' . $uploadId . '.part';
            $metadataPath = $directory . '/' . $uploadId . '.json';
            $metadata = $this->loadOrCreateMetadata($metadataPath, $request, $totalSize, $user, $filesystem);

            if (($metadata['complete'] ?? false) === true) {
                return $this->completedResponse($metadata);
            }

            $this->assertMetadataMatches($metadata, $request, $totalSize);
            $this->writeChunk($partPath, $chunk->getPathname(), $offset, $chunkSize);

            $received = (int) filesize($partPath);
            if ($received < $totalSize) {
                return new JsonResponse([
                    'success' => true,
                    'complete' => false,
                    'received' => $received,
                    'total' => $totalSize,
                ], 202);
            }
            if ($received !== $totalSize) {
                throw new \RuntimeException('The assembled upload has an invalid size.');
            }

            if (($metadata['operation'] ?? 'asset') === 'importZip') {
                $metadata['response'] = $this->prepareZipImport($partPath, $metadata, $user);
            } else {
                $asset = $this->createAsset($partPath, $metadata, $user);
                $metadata['assetId'] = $asset->getId();
            }
            $metadata['complete'] = true;
            $filesystem->dumpFile($metadataPath, json_encode($metadata, JSON_THROW_ON_ERROR));
            $filesystem->remove($partPath);

            return $this->completedResponse($metadata, 201);
        } catch (\Throwable $exception) {
            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400;

            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $status);
        }
    }

    /** @return array<string, mixed> */
    private function loadOrCreateMetadata(
        string $path,
        Request $request,
        int $totalSize,
        User $user,
        Filesystem $filesystem,
    ): array {
        if (is_file($path)) {
            $metadata = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($metadata)) {
                throw new \RuntimeException('Invalid upload metadata.');
            }

            return $metadata;
        }

        if ((int) $request->request->get('offset', -1) !== 0) {
            throw new \InvalidArgumentException('The first upload chunk is missing.');
        }

        $filename = ElementService::getValidKey((string) $request->request->get('filename', ''), 'asset');
        if ($filename === '') {
            throw new \InvalidArgumentException('The filename is empty.');
        }

        $metadata = [
            'filename' => $filename,
            'totalSize' => $totalSize,
            'operation' => $request->request->get('operation') === 'importZip' ? 'importZip' : 'asset',
            'parentId' => (int) $request->request->get('parentId', 0),
            'parentPath' => (string) $request->request->get('parentPath', ''),
            'dir' => (string) $request->request->get('dir', ''),
            'allowOverwrite' => filter_var($request->request->get('allowOverwrite', false), FILTER_VALIDATE_BOOL),
            'userId' => (int) $user->getId(),
            'createdAt' => time(),
            'complete' => false,
        ];
        $filesystem->dumpFile($path, json_encode($metadata, JSON_THROW_ON_ERROR));

        return $metadata;
    }

    /** @param array<string, mixed> $metadata */
    private function assertMetadataMatches(array $metadata, Request $request, int $totalSize): void
    {
        if ((int) ($metadata['totalSize'] ?? 0) !== $totalSize
            || (string) ($metadata['filename'] ?? '') !== ElementService::getValidKey((string) $request->request->get('filename', ''), 'asset')
        ) {
            throw new \InvalidArgumentException('The chunk does not match this upload.');
        }
    }

    private function writeChunk(string $partPath, string $chunkPath, int $offset, int $chunkSize): void
    {
        $destination = fopen($partPath, 'c+b');
        $source = fopen($chunkPath, 'rb');
        if ($destination === false || $source === false) {
            if (is_resource($destination)) {
                fclose($destination);
            }
            if (is_resource($source)) {
                fclose($source);
            }
            throw new \RuntimeException('Unable to open the upload chunk.');
        }

        try {
            if (!flock($destination, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock the upload.');
            }

            $statistics = fstat($destination);
            if ($statistics === false) {
                throw new \RuntimeException('Unable to inspect the assembled upload.');
            }
            $currentSize = (int) $statistics['size'];
            if ($currentSize >= $offset + $chunkSize) {
                return;
            }
            if ($currentSize !== $offset) {
                throw new \RuntimeException('Upload chunks arrived out of order.');
            }
            if (fseek($destination, $offset) !== 0 || stream_copy_to_stream($source, $destination) !== $chunkSize) {
                throw new \RuntimeException('Unable to store the upload chunk.');
            }
            fflush($destination);
        } finally {
            flock($destination, LOCK_UN);
            fclose($source);
            fclose($destination);
        }
    }

    /** @param array<string, mixed> $metadata */
    private function createAsset(string $sourcePath, array $metadata, User $user): Asset
    {
        $parentId = (int) ($metadata['parentId'] ?? 0);
        $parentPath = trim((string) ($metadata['parentPath'] ?? ''));
        $dir = trim((string) ($metadata['dir'] ?? ''), '/ ');

        if ($dir !== '' && str_contains($dir, '..')) {
            throw new \InvalidArgumentException('Invalid target directory.');
        }

        if ($parentId > 0) {
            $parent = Asset::getById($parentId);
        } elseif ($parentPath !== '') {
            $parent = Asset::getByPath($parentPath);
        } else {
            $parent = Asset::getById(1);
        }
        if (!$parent instanceof Asset\Folder) {
            throw new \InvalidArgumentException('The target asset folder does not exist.');
        }

        if ($dir !== '') {
            $parent = Asset\Service::createFolderByPath($parent->getRealFullPath() . '/' . $dir);
        }
        if (!$parent->isAllowed('create', $user)) {
            throw $this->createAccessDeniedException('Missing permission to create assets in the target folder.');
        }

        $filename = (string) $metadata['filename'];
        $targetPath = rtrim($parent->getRealFullPath(), '/') . '/' . $filename;
        $allowOverwrite = (bool) ($metadata['allowOverwrite'] ?? false);

        if ($allowOverwrite && Asset\Service::pathExists($targetPath)) {
            $asset = Asset::getByPath($targetPath);
            if (!$asset instanceof Asset || !$asset->isAllowed('publish', $user)) {
                throw $this->createAccessDeniedException('Missing permission to overwrite the existing asset.');
            }
            $stream = fopen($sourcePath, 'rb');
            if ($stream === false) {
                throw new \RuntimeException('Unable to read the assembled upload.');
            }
            $asset->setStream($stream);
            $asset->setUserModification($user->getId());
            $asset->save();

            return $asset;
        }

        if (!$allowOverwrite) {
            $filename = $this->safeFilename($parent->getRealFullPath(), $filename);
        }

        return Asset::create($parent->getId(), [
            'filename' => $filename,
            'sourcePath' => $sourcePath,
            'userOwner' => $user->getId(),
            'userModification' => $user->getId(),
        ]);
    }

    private function safeFilename(string $parentPath, string $filename): string
    {
        $path = pathinfo($filename);
        $base = $path['filename'];
        $extension = isset($path['extension']) ? '.' . $path['extension'] : '';
        $candidate = $filename;

        for ($suffix = 1; Asset\Service::pathExists(rtrim($parentPath, '/') . '/' . $candidate); ++$suffix) {
            $candidate = $base . '_' . $suffix . $extension;
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function prepareZipImport(string $sourcePath, array $metadata, User $user): array
    {
        $parent = Asset::getById((int) ($metadata['parentId'] ?? 0));
        if (!$parent instanceof Asset\Folder) {
            throw new \InvalidArgumentException('The target asset folder does not exist.');
        }
        if (!$parent->isAllowed('create', $user)) {
            throw $this->createAccessDeniedException('Missing permission to import assets into the target folder.');
        }

        $jobId = uniqid();
        $zipPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $jobId . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($sourcePath) !== true) {
            throw new \InvalidArgumentException('Could not open the ZIP file.');
        }

        $jobs = [];
        $filesPerJob = 5;
        $jobAmount = (int) ceil($zip->numFiles / $filesPerJob);
        $zip->close();
        if ($jobAmount < 1) {
            throw new \InvalidArgumentException('The ZIP file is empty.');
        }

        if (!copy($sourcePath, $zipPath)) {
            throw new \RuntimeException('Unable to prepare the ZIP import.');
        }

        for ($index = 0; $index < $jobAmount; ++$index) {
            $jobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_asset_importzipfiles'),
                'method' => 'POST',
                'params' => [
                    'parentId' => $parent->getId(),
                    'offset' => $index * $filesPerJob,
                    'limit' => $filesPerJob,
                    'jobId' => $jobId,
                    'last' => ($index + 1) >= $jobAmount ? 'true' : '',
                    'allowOverwrite' => ($metadata['allowOverwrite'] ?? false) ? 'true' : 'false',
                ],
            ]];
        }

        return [
            'success' => true,
            'complete' => true,
            'jobs' => $jobs,
            'jobId' => $jobId,
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function completedResponse(array $metadata, int $status = 200): JsonResponse
    {
        if (($metadata['operation'] ?? 'asset') === 'importZip') {
            $response = $metadata['response'] ?? null;
            if (!is_array($response)) {
                throw new \RuntimeException('The prepared ZIP import no longer exists.');
            }

            return new JsonResponse($response, $status);
        }

        $asset = Asset::getById((int) ($metadata['assetId'] ?? 0));
        if (!$asset instanceof Asset) {
            throw new \RuntimeException('The uploaded asset no longer exists.');
        }

        return new JsonResponse([
            'success' => true,
            'complete' => true,
            'asset' => [
                'id' => $asset->getId(),
                'path' => $asset->getFullPath(),
                'type' => $asset->getType(),
            ],
        ], $status);
    }

    private function removeStaleUploads(string $directory, Filesystem $filesystem): void
    {
        foreach (glob($directory . '/*.{part,json}', GLOB_BRACE) ?: [] as $path) {
            if (is_file($path) && filemtime($path) < time() - self::STALE_AFTER_SECONDS) {
                $filesystem->remove($path);
            }
        }
    }
}
