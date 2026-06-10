<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\DashboardDefaultsSubscriber;
use Codeception\Test\Unit;
use Symfony\Component\EventDispatcher\GenericEvent;

final class DashboardDefaultsSubscriberTest extends Unit
{
    public function testExpensivePortletsAreRemovedFromExistingDashboard(): void
    {
        $event = new GenericEvent(null, [
            'result' => [
                'dashboards' => [
                    'predefined' => [
                        'Theo' => [
                            'positions' => [[
                                ['id' => 1, 'type' => 'pimcore.layout.portlets.familyLaunchModels', 'config' => null],
                                ['id' => 2, 'type' => 'pimcore.layout.portlets.modifiedObjects', 'config' => null],
                                ['id' => 4, 'type' => 'pimcore.layout.portlets.modifiedAssets', 'config' => null],
                                ['id' => 5, 'type' => 'pimcore.layout.portlets.modificationStatistic', 'config' => null],
                            ]],
                        ],
                    ],
                ],
            ],
        ]);

        (new DashboardDefaultsSubscriber())->addDefaultDashboard($event);

        $dashboard = $event->getArgument('result')['dashboards']['predefined']['Theo'];
        $types = array_column($dashboard['positions'][0], 'type');

        self::assertSame(['pimcore.layout.portlets.familyLaunchModels'], $types);
    }
}
