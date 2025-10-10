<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Pimcore\Tool\Console as PimcoreConsole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/admin/datahub-supervisor')]
final class DataHubSupervisorController extends AbstractController
{
    public function __construct(private TokenStorageInterface $tokenStorage) {}

    // ====== Config/paths ======
    private const CACHE_NS = 'datahub_single';
    private const PID_KEY  = 'pid';

    private function logPath(): string
    {
        return \PIMCORE_PROJECT_ROOT . '/var/log/datahub-importer.log';
    }

    private function stopFlag(): string
    {
        $dir = \PIMCORE_PROJECT_ROOT . '/var/tmp';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir . '/datahub.stop';
    }

    private function phpCli(): string
    {
        // Use Pimcore helper to get the *CLI* php binary (not php-fpm)
        return PimcoreConsole::getPhpCli() ?: '/usr/bin/php';
    }

    private function getAdminUser(): ?object
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) return null;
        $user = $token->getUser();
        return \is_object($user) ? $user : null; // Pimcore wraps admin users
    }

    private function assertAdminOrAllowed(): void
    {
        $user = $this->getAdminUser();
        // Pimcore admin user wrappers expose isAdmin() / isAllowed()
        if (
            !$user
            || (method_exists($user, 'isAdmin') && !$user->isAdmin()
                && !(method_exists($user, 'isAllowed') && $user->isAllowed('datahub_control')))
        ) {
            throw $this->createAccessDeniedException('Not allowed');
        }
    }

    private function isRunning(int $pid): bool
    {
        if ($pid <= 0) return false;
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }
        return file_exists("/proc/{$pid}");
    }

    private function tail(string $file, int $lines): string
    {
        if (!is_file($file)) return '';
        $f = @fopen($file, 'rb'); if (!$f) return '';
        $buf=''; $pos=-1; $cnt=0; fseek($f,0,SEEK_END); $size=ftell($f);
        while (-$pos < $size && $cnt <= $lines) {
            fseek($f, $pos--, SEEK_END);
            $ch = fgetc($f); $buf = $ch . $buf;
            if ($ch === "\n") $cnt++;
        }
        fclose($f); return $buf;
    }

    // ====== Endpoints ======

    #[Route('/start', name: 'datahub_supervisor_start', methods: ['POST'])]
    public function start(Request $r): JsonResponse
    {
        $this->assertAdminOrAllowed();

        $cache   = new FilesystemAdapter(self::CACHE_NS);
        $pidItem = $cache->getItem(self::PID_KEY);

        if ($pidItem->isHit() && $this->isRunning((int)$pidItem->get())) {
            return new JsonResponse(['ok' => false, 'msg' => 'Already running'], 409);
        }

        @unlink($this->stopFlag());
        @touch($this->logPath());

        $profile = preg_replace('/[^\w\-]+/','', (string)$r->request->get('profile','default'));
        $extra   = (string)$r->request->get('extra','');

        $cmd = [
            $this->phpCli(),
            'bin/console',
            'datahub:data-importer:process-queue-sequential',
            '--no-interaction',
            '--ansi',
        ];
        if ($extra) {
            foreach (preg_split('/\s+/', trim($extra)) as $opt) {
                if ($opt !== '') $cmd[] = $opt;
            }
        }

        $wrapper = sprintf(
            'while [ ! -f %s ]; do %s >> %s 2>&1; code=$?; break; done; exit $code',
            escapeshellarg($this->stopFlag()),
            implode(' ', array_map('escapeshellarg', $cmd)),
            escapeshellarg($this->logPath())
        );

        $proc = Process::fromShellCommandline($wrapper, \PIMCORE_PROJECT_ROOT);
        $proc->disableOutput();
        $proc->setTimeout(null);

        try {
            $proc->start(); // non-blocking
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'msg' => 'Failed to start process',
                'error' => $e->getMessage(),
                'php_cli' => $this->phpCli(),
                'cmd' => implode(' ', $cmd),
            ], 500);
        }

        $pid = (int)$proc->getPid();
        $pidItem->set($pid);
        $pidItem->expiresAfter(6 * 3600);
        $cache->save($pidItem);

        return new JsonResponse([
            'ok' => true,
            'pid' => $pid,
            'php_cli' => $this->phpCli(),
            'cmd' => implode(' ', $cmd),
            'log' => $this->logPath(),
        ]);
    }

    #[Route('/stop', name: 'datahub_supervisor_stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        $this->assertAdminOrAllowed();

        @touch($this->stopFlag());
        $cache = new FilesystemAdapter(self::CACHE_NS);
        $cache->deleteItem(self::PID_KEY);

        return new JsonResponse(['ok' => true, 'msg' => 'Stop flag created']);
    }

    #[Route('/status', name: 'datahub_supervisor_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $this->assertAdminOrAllowed();

        $cache = new FilesystemAdapter(self::CACHE_NS);
        $pid   = (int)$cache->getItem(self::PID_KEY)->get();
        $alive = $this->isRunning($pid);

        if (!$alive) {
            $cache->deleteItem(self::PID_KEY);
        } else {
            $item = $cache->getItem(self::PID_KEY);
            $item->set($pid);
            $item->expiresAfter(6 * 3600);
            $cache->save($item);
        }

        return new JsonResponse([
            'ok' => true,
            'workers' => [
                ['name' => 'datahub-single', 'state' => $alive ? 'RUNNING' : 'STOPPED']
            ],
            'pid' => $pid ?: null,
        ]);
    }

    #[Route('/log', name: 'datahub_supervisor_log', methods: ['GET'])]
    public function log(Request $r): JsonResponse
    {
        $this->assertAdminOrAllowed();

        $lines = max(50, min(2000, (int)$r->query->get('lines', 400)));
        return new JsonResponse(['log' => $this->tail($this->logPath(), $lines)]);
    }
}
