<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ObjectKeyAutofillSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreAdd',
        ];
    }

    public function onPreAdd(DataObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!method_exists($object, 'getClassName') || !method_exists($object, 'getKey')) {
            return;
        }

        $key = trim((string) $object->getKey());
        if ($key === '') {
            return;
        }

        match (strtolower((string) $object->getClassName())) {
            'family', 'model' => $this->autofillCodeAndName($object, $key),
            'frame' => $this->setIfEmpty($object, 'Code', $key),
            default => null,
        };
    }

    private function autofillCodeAndName(object $object, string $key): void
    {
        if (!preg_match('/^\s*(.+?)\s+-\s+(.+?)\s*$/', $key, $matches)) {
            return;
        }

        $this->setIfEmpty($object, 'Code', $matches[1]);
        $this->setIfEmpty($object, 'Name', $matches[2]);
    }

    private function setIfEmpty(object $object, string $fieldName, string $value): void
    {
        $getter = 'get' . $fieldName;
        $setter = 'set' . $fieldName;

        if (!method_exists($object, $getter) || !method_exists($object, $setter)) {
            return;
        }

        if (trim((string) ($object->$getter() ?? '')) !== '') {
            return;
        }

        $object->$setter($value);
    }
}
