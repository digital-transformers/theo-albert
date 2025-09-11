<?php declare(strict_types=1);

namespace App\EventListener;

use Google\Service\CloudSearch\Menu;
use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\Model\Asset\PreAddEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Pimcore\Event\Admin\MenuEvents;
use Pimcore\Event\Admin\MenuEvent;
use Pimcore\Bundle\AdminBundle\Event\AdminEvents;


/**
 * class PimcoreAdminListener
 */
final class PimcoreAdminListener
{

    public function addJSFiles(PathsEvent $event): void
    {
        $event->setPaths(
            array_merge(
                $event->getPaths(),
                [
                    '/app/admin/color-autoname.js',
                ]
            )
        );
    }

}
