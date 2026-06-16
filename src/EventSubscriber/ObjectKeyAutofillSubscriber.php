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
            DataObjectEvents::PRE_UPDATE => 'onPreUpdate',
        ];
    }

    public function onPreAdd(DataObjectEvent $event): void
    {
        $this->normalizeObject($event->getObject(), true);
    }

    public function onPreUpdate(DataObjectEvent $event): void
    {
        $this->normalizeObject($event->getObject(), false);
    }

    private function normalizeObject(object $object, bool $autofillFromKey): void
    {
        if (!method_exists($object, 'getClassName') || !method_exists($object, 'getKey')) {
            return;
        }

        $key = trim((string) $object->getKey());
        if ($key === '') {
            return;
        }

        match (strtolower((string) $object->getClassName())) {
            'family', 'model' => $this->normalizeFamilyOrModel($object, $key, $autofillFromKey),
            'frame' => $this->setIfEmpty($object, 'Code', $key),
            default => null,
        };
    }

    private function normalizeFamilyOrModel(object $object, string $key, bool $autofillFromKey): void
    {
        if ($autofillFromKey) {
            $this->autofillCodeAndName($object, $key);
        }

        $this->syncKeyFromCodeAndName($object);
    }

    private function autofillCodeAndName(object $object, string $key): void
    {
        if (!preg_match('/^\s*(.+?)\s+-\s+(.+?)\s*$/', $key, $matches)) {
            return;
        }

        $this->setIfEmpty($object, 'Code', $matches[1]);
        $this->setIfEmpty($object, 'Name', $matches[2]);
    }

    private function syncKeyFromCodeAndName(object $object): void
    {
        if (
            !method_exists($object, 'getCode')
            || !method_exists($object, 'getName')
            || !method_exists($object, 'getKey')
            || !method_exists($object, 'setKey')
        ) {
            return;
        }

        $code = trim((string) ($object->getCode() ?? ''));
        $name = trim((string) ($object->getName() ?? ''));
        if ($code === '' || $name === '') {
            return;
        }

        $key = trim(str_replace(['/', '\\'], '-', $code . ' - ' . $name));
        if ($key === '') {
            return;
        }

        if (trim((string) $object->getKey()) === $key) {
            return;
        }

        $object->setKey($key);
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
