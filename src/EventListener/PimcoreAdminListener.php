<?php declare(strict_types=1);

namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PimcoreAdminListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ BundleManagerEvents::JS_PATHS => 'addJSFiles' ];
    }

    public function addJSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/app/admin/color-autoname.js',
            '/app/admin/model-generate-frames.js',
            '/app/admin/datahub-control.js',
            '/app/admin/family-launch-portlet.js',
            '/app/admin/default-dashboard.js',
        ]);
    }
}
