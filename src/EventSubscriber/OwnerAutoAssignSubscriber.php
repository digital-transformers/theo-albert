<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OwnerAutoAssignSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageUserResolver $userResolver) {}

    public static function getSubscribedEvents(): array
    {
        return ['pimcore.dataobject.preAdd' => 'onPreAdd'];
    }

    public function onPreAdd(DataObjectEvent $event): void
    {
        $user = $this->userResolver->getUser();
        $obj  = $event->getObject();
        if ($user && method_exists($obj, 'getUserOwner') && !$obj->getUserOwner()) {
            $obj->setUserOwner((int)$user->getId());
        }
    }
}
