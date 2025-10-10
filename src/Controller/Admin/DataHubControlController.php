<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Controller\KernelControllerEventInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/datahub-control")
 */
final class DataHubControlController extends AbstractController
{
    private const PID_CACHE_KEY = 'datahub_importer_pid';
    private const RUN_CACHE_KEY = 'datahub_importer_run';
    private const STOP_FLAG_FILE = PIMCORE_PROJECT_ROOT . '/var/tmp/datahub.stop';
    private const LOG_FILE = PIMCORE_PROJECT_ROOT . '/var/log/datahub-importer.log';

    #[Route('/start', name: 'datahub_control_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('datahub_control');

        $profile = (string)($request->request->get('profile') ?? 'default');
        $dryRun  = (bool)$request->request->get('dryRun', false);
        $extra   = (string)($request->request->get('extra') ?? ''); // e.g. "--limit=500"

        // prevent multiple runs
        $cache = new FilesystemAdapter(namespace: 'datahub_control');
        if ($cache->getItem(self::PID_CACHE_KEY)->isHit()) {
            return new JsonResponse(['ok' => false, 'msg' => 'Importer already running'], 409);
        }

        @unlink(self::STOP_FLAG_FILE); // clear any previous stop signal
        @touch(self::LOG_FILE);        // ensure log file exists

        $cmd = [
            PHP_BINARY, 'bin/console',
            'datahub:data-importer:process-queue-sequential',
            '--profile=' . $profile,
            '--no-interaction',
            '--ansi',
        ];

        if ($dryRun) {
            $cmd[] = '--dry-run';
        }
        if ($extra !== '') {
            // naive split; keep it simple or parse better if you like
            foreach (preg_split('/\s+/', trim($extra)) as $opt) {
                $cmd[] = $opt;
            }
        }

        // Wrap the command to continuously append logs and obey a stop-flag
        $shell = sprintf(
            'while [ ! -f %s ]; do %s >> %s 2>&1; code=$?; break; done; exit $code',
            escapeshellarg(self::STOP_FLAG_FILE),
            implode(' ', array_map('escapeshellarg', $cmd)),
            escapeshellarg(self::LOG_FILE)
        );

        $process = Process::fromShellCommandline($shell, PIMCORE_PROJECT_ROOT);
        $process->disableOutput(); // we log to file
        $process->setTimeout(null);
        $process->start(); // non-blocking

        // persist PID + run meta
        $pidItem = $cache->getItem(self::PID_CACHE_KEY);
        $pidItem->set($process->getPid());
        $pidItem->expiresAfter(6*60*60);
        $cache->save($pidItem);

        $runItem = $cache->getItem(self::RUN_CACHE_KEY);
        $runItem->set([
            'profile' => $profile,
            'startedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'by' => $this->getUser()?->getName() ?? 'unknown',
            'dryRun' => $dryRun,
            'extra' => $extra,
        ]);
        $cache->save($runItem);

        return new JsonResponse(['ok' => true, 'pid' => $process->getPid()]);
    }

    #[Route('/stop', name: 'datahub_control_stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        $this->denyAccessUnlessGranted('datahub_control');

        // graceful stop: command loop exits when stop flag appears
        @touch(self::STOP_FLAG_FILE);

        // best-effort kill if still alive after a delay (handled by frontend polling)
        return new JsonResponse(['ok' => true, 'msg' => 'Stop signal sent']);
    }

    #[Route('/status', name: 'datahub_control_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $this->denyAccessUnlessGranted('datahub_control');

        $cache = new FilesystemAdapter(namespace: 'datahub_control');
        $pid   = $cache->getItem(self::PID_CACHE_KEY)->get();
        $run   = $cache->getItem(self::RUN_CACHE_KEY)->get();

        $running = false;
        if ($pid) {
            $running = @posix_kill((int)$pid, 0); // check if alive
            if (!$running) {
                $cache->deleteItem(self::PID_CACHE_KEY);
            }
        }

        // (Optional) parse a tiny progress marker written by a custom OutputSubscriber
        $progress = $this->readProgressFromLog();

        return new JsonResponse([
            'running' => $running,
            'pid' => $pid,
            'run' => $run,
            'progress' => $progress,
        ]);
    }

    #[Route('/log', name: 'datahub_control_log', methods: ['GET'])]
    public function log(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('datahub_control');

        $lines = (int)($request->query->get('lines') ?? 200);
        $tail  = $this->tail(self::LOG_FILE, max(50, min(2000, $lines)));

        return new JsonResponse(['log' => $tail]);
    }

    private function tail(string $file, int $lines = 200): string
    {
        if (!is_file($file)) {
            return '';
        }
        // Fast tail
        $f = fopen($file, 'rb'); if (!$f) return '';
        $buffer = ''; $pos = -1; $lineCnt = 0;
        fseek($f, 0, SEEK_END);
        $filesize = ftell($f);
        while (-$pos < $filesize && $lineCnt <= $lines) {
            fseek($f, $pos--, SEEK_END);
            $ch = fgetc($f);
            $buffer = $ch . $buffer;
            if ($ch === "\n") $lineCnt++;
        }
        fclose($f);
        return $buffer;
    }

    private function readProgressFromLog(): array
    {
        // Convention: the importer logs lines like:
        // [progress] processed=123 total=456 failed=2
        $tail = $this->tail(self::LOG_FILE, 400);
        if (preg_match('/\[progress\]\s+processed=(\d+)\s+total=(\d+)\s+failed=(\d+)/', $tail, $m)) {
            return ['processed' => (int)$m[1], 'total' => (int)$m[2], 'failed' => (int)$m[3]];
        }
        return [];
    }
}
