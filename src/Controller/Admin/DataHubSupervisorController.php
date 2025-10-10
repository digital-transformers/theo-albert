<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/datahub-supervisor')]
final class DataHubSupervisorController extends AbstractController
{
    private const PID = 'datahub_pid';
    private const LOG = '/var/log/pimcore/datahub-importer.log';
    private const STOP = '/var/tmp/datahub.stop';

    #[Route('/start', methods: ['POST'])]
    public function start(Request $r): JsonResponse {
        $cache = new FilesystemAdapter('datahub');
        if ($cache->getItem(self::PID)->isHit()) {
            return new JsonResponse(['ok'=>false,'msg'=>'Already running'], 409);
        }
        @unlink(self::STOP);
        @touch(self::LOG);

        $profile = preg_replace('/[^\w\-]+/','', (string)$r->request->get('profile','default'));
        $extra   = (string)$r->request->get('extra','');

        $cmd = [
            PHP_BINARY,'bin/console','datahub:data-importer:process-queue-sequential',
            '--profile='.$profile,'--no-interaction','--ansi'
        ];
        if ($extra) foreach (preg_split('/\s+/', trim($extra)) as $opt){ $cmd[]=$opt; }

        $wrapper = sprintf(
          'while [ ! -f %s ]; do %s >> %s 2>&1; code=$?; break; done; exit $code',
          escapeshellarg(self::STOP),
          implode(' ', array_map('escapeshellarg',$cmd)),
          escapeshellarg(self::LOG)
        );

        $p = Process::fromShellCommandline($wrapper, \PIMCORE_PROJECT_ROOT);
        $p->disableOutput(); $p->setTimeout(null); $p->start();

        $item = $cache->getItem(self::PID); $item->set($p->getPid()); $cache->save($item);

        return new JsonResponse(['ok'=>true,'pid'=>$p->getPid()]);
    }

    #[Route('/stop', methods: ['POST'])]
    public function stop(): JsonResponse {
        @touch(self::STOP);
        $cache = new FilesystemAdapter('datahub'); $cache->deleteItem(self::PID);
        return new JsonResponse(['ok'=>true]);
    }

    #[Route('/status', methods: ['GET'])]
    public function status(): JsonResponse {
        $cache = new FilesystemAdapter('datahub');
        $pid = (int)$cache->getItem(self::PID)->get();
        $running = $pid ? @posix_kill($pid, 0) : false;
        if (!$running) $cache->deleteItem(self::PID);
        return new JsonResponse(['ok'=>true,'workers'=>[
            ['name'=>'datahub-single','state'=>$running?'RUNNING':'STOPPED']
        ]]);
    }

    #[Route('/log', methods: ['GET'])]
    public function log(Request $r): JsonResponse {
        $lines = max(50, min(2000, (int)$r->query->get('lines', 400)));
        if (!is_file(self::LOG)) return new JsonResponse(['log'=>'']);
        return new JsonResponse(['log'=>$this->tail(self::LOG,$lines)]);
    }

    private function tail(string $file, int $lines): string {
        $f=@fopen($file,'rb'); if(!$f) return '';
        $buf=''; $pos=-1; $cnt=0; fseek($f,0,SEEK_END); $size=ftell($f);
        while (-$pos<$size && $cnt<=$lines){ fseek($f,$pos--,SEEK_END); $ch=fgetc($f); $buf=$ch.$buf; if($ch==="\n") $cnt++; }
        fclose($f); return $buf;
    }
}
