<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Pimcore\Model\DataObject\Process;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/sap-sync')]
final class SapSyncProcessController extends AbstractController
{
    #[Route('/process-codes', name: 'admin_sap_sync_process_codes', methods: ['GET'])]
    public function processCodes(): JsonResponse
    {
        $listing = new Process\Listing();
        $listing->setUnpublished(true);
        $processes = [];
        foreach ($listing as $process) {
            $code = trim((string) $process->getCode());
            if ($code === '') {
                continue;
            }
            $processes[strtolower($code)] = [
                'id' => $process->getId(),
                'code' => $code,
                'name' => trim((string) $process->getName()),
                'order' => $process->getOrder() === null ? null : (int) $process->getOrder(),
            ];
        }
        uasort($processes, static function (array $left, array $right): int {
            return [$left['order'] ?? PHP_INT_MAX, strtolower($left['code'])]
                <=> [$right['order'] ?? PHP_INT_MAX, strtolower($right['code'])];
        });

        return new JsonResponse(['success' => true, 'processes' => array_values($processes)]);
    }
}
