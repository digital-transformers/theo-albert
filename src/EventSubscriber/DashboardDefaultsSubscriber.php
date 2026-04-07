<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class DashboardDefaultsSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_DASHBOARD_KEY = 'Theo';

    private const DEFAULT_DASHBOARD = [
        'positions' => [
            [
                [
                    'id' => 1,
                    'type' => 'pimcore.layout.portlets.familyLaunchModels',
                    'config' => null,
                ],
            ],
            [
                [
                    'id' => 2,
                    'type' => 'pimcore.layout.portlets.modifiedObjects',
                    'config' => null,
                ],
                [
                    'id' => 4,
                    'type' => 'pimcore.layout.portlets.modifiedAssets',
                    'config' => null,
                ],
                [
                    'id' => 5,
                    'type' => 'pimcore.layout.portlets.modificationStatistic',
                    'config' => null,
                ],
            ],
        ],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::PERSPECTIVE_POST_GET_RUNTIME => 'addDefaultDashboard',
        ];
    }

    public function addDefaultDashboard(GenericEvent $event): void
    {
        $result = $event->getArgument('result');

        if (!is_array($result)) {
            return;
        }

        if (!isset($result['dashboards']) || !is_array($result['dashboards'])) {
            $result['dashboards'] = [];
        }

        if (!isset($result['dashboards']['predefined']) || !is_array($result['dashboards']['predefined'])) {
            $result['dashboards']['predefined'] = [];
        }

        $result['dashboards']['predefined'][self::DEFAULT_DASHBOARD_KEY] ??= self::DEFAULT_DASHBOARD;

        $event->setArgument('result', $result);
    }
}
