<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/datahub-supervisor')]
final class DataHubSupervisorController extends AbstractController
{
    #[Route('/status', name: 'datahub_supervisor_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse([
            'ok' => true,
            'workers' => [
                ['name' => 'datahub-importer_00', 'state' => 'STOPPED'],
                ['name' => 'datahub-importer_01', 'state' => 'STOPPED'],
            ],
        ]);
    }

    #[Route('/log', name: 'datahub_supervisor_log', methods: ['GET'])]
    public function log(Request $request): JsonResponse
    {
        return new JsonResponse(['log' => ""]);
    }

    #[Route('/start', name: 'datahub_supervisor_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        return new JsonResponse(['ok' => true, 'msg' => 'start requested']);
    }

    #[Route('/stop', name: 'datahub_supervisor_stop', methods: ['POST'])]
    public function stop(): JsonResponse
    {
        return new JsonResponse(['ok' => true, 'msg' => 'stop requested']);
    }
}
