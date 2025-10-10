<?php declare(strict_types=1);

namespace App\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PimcoreAdminListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::JS_PATHS => 'addJSFiles',
        ];
    }

    public function addJSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/app/admin/datahub-control.js',      
            '/app/admin/color-autoname.js',
        ]);
    }
}
