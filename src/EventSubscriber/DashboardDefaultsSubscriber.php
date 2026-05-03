<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Tool\Admin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class DashboardDefaultsSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_DASHBOARD_KEY = 'Theo';
    private const QUALITY_REMARKS_PORTLET_TYPE = 'pimcore.layout.portlets.qualityRemarks';
    private const QUALITY_REMARKS_PORTLET = [
        'id' => 6,
        'type' => self::QUALITY_REMARKS_PORTLET_TYPE,
        'config' => null,
    ];

    private const DEFAULT_DASHBOARD = [
        'positions' => [
            [
                [
                    'id' => 1,
                    'type' => 'pimcore.layout.portlets.familyLaunchModels',
                    'config' => null,
                ],
                self::QUALITY_REMARKS_PORTLET,
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

        $dashboard = $result['dashboards']['predefined'][self::DEFAULT_DASHBOARD_KEY] ?? self::DEFAULT_DASHBOARD;
        if ($this->currentUserCanAccessQualityControl()) {
            $dashboard = $this->ensurePortlet($dashboard, self::QUALITY_REMARKS_PORTLET, 0);
        } else {
            $dashboard = $this->removePortlet($dashboard, self::QUALITY_REMARKS_PORTLET_TYPE);
        }

        $result['dashboards']['predefined'][self::DEFAULT_DASHBOARD_KEY] = $dashboard;

        $event->setArgument('result', $result);
    }

    private function currentUserCanAccessQualityControl(): bool
    {
        $user = Admin::getCurrentUser();
        $isAdmin = $user && (
            (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (method_exists($user, 'getAdmin') && $user->getAdmin())
        );
        $canUseQualityControl = $user && method_exists($user, 'isAllowed') && $user->isAllowed(QualityControlSubscriber::PERMISSION_KEY);

        return $isAdmin || $canUseQualityControl;
    }

    /**
     * @param array<string, mixed> $dashboard
     * @param array{id: int, type: string, config: mixed} $portlet
     *
     * @return array<string, mixed>
     */
    private function ensurePortlet(array $dashboard, array $portlet, int $column): array
    {
        if ($this->hasPortlet($dashboard, $portlet['type'])) {
            return $dashboard;
        }

        if (!isset($dashboard['positions']) || !is_array($dashboard['positions'])) {
            $dashboard['positions'] = [];
        }

        if (!isset($dashboard['positions'][$column]) || !is_array($dashboard['positions'][$column])) {
            $dashboard['positions'][$column] = [];
        }

        $dashboard['positions'][$column][] = $portlet;

        return $dashboard;
    }

    /**
     * @param array<string, mixed> $dashboard
     */
    private function hasPortlet(array $dashboard, string $type): bool
    {
        if (!isset($dashboard['positions']) || !is_array($dashboard['positions'])) {
            return false;
        }

        foreach ($dashboard['positions'] as $column) {
            if (!is_array($column)) {
                continue;
            }

            foreach ($column as $portlet) {
                if (is_array($portlet) && ($portlet['type'] ?? null) === $type) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $dashboard
     *
     * @return array<string, mixed>
     */
    private function removePortlet(array $dashboard, string $type): array
    {
        if (!isset($dashboard['positions']) || !is_array($dashboard['positions'])) {
            return $dashboard;
        }

        foreach ($dashboard['positions'] as $columnIndex => $column) {
            if (!is_array($column)) {
                continue;
            }

            $dashboard['positions'][$columnIndex] = array_values(array_filter(
                $column,
                static fn (mixed $portlet): bool => !is_array($portlet) || ($portlet['type'] ?? null) !== $type
            ));
        }

        return $dashboard;
    }
}
