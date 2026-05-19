<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\AutomaticImageLinker;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AutomaticImageLinkingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AutomaticImageLinker $imageLinker,
        private readonly TokenStorageUserResolver $userResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::POST_ADD => 'onPostAdd',
        ];
    }

    public function onPostAdd(AssetEvent $event): void
    {
        if ($event->hasArgument('automatic_image_linking_skip_event') && $event->getArgument('automatic_image_linking_skip_event') === true) {
            return;
        }

        $asset = $event->getAsset();
        if (!$this->imageLinker->shouldAutoProcess($asset)) {
            return;
        }

        $this->imageLinker->processAsset($asset, $this->userResolver->getUser(), true);
    }
}
