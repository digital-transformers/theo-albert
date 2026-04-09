<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Color;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Model as ModelObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ModelFinalProductDetailsColorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $model = $event->getObject();
        if (!$model instanceof ModelObject) {
            return;
        }

        $collection = $model->getFinalProductDetails();
        if (!$collection instanceof Fieldcollection) {
            return;
        }

        foreach ($collection as $item) {
            if (!$item || $item->getType() !== 'finalProductProcess') {
                continue;
            }

            $colorIds = $this->normalizeColorIds($this->getFieldValue($item, 'colors'));
            $this->setFieldValue($item, 'composingColors', $this->buildColorMetadataFromIds($colorIds));
        }
    }

    /**
     * @return list<int|string>
     */
    private function normalizeColorIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = $value === '' ? [] : explode(',', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            $id = is_array($item) ? ($item['id'] ?? $item['value'] ?? null) : $item;
            if (!is_scalar($id)) {
                continue;
            }

            $normalizedId = trim((string) $id);
            if ($normalizedId === '' || in_array($normalizedId, $ids, true)) {
                continue;
            }

            $ids[] = $normalizedId;
        }

        return $ids;
    }

    /**
     * @param list<int|string> $colorIds
     *
     * @return list<ObjectMetadata>
     */
    private function buildColorMetadataFromIds(array $colorIds): array
    {
        $metadata = [];

        foreach ($colorIds as $colorId) {
            $color = Color::getById((int) $colorId, ['force' => true]);
            if (!$color instanceof Color) {
                continue;
            }

            $item = new ObjectMetadata('composingColors', ['name', 'relevant'], $color);
            $item->setName($this->normalizeString($color->getName()));
            $item->setRelevant(true);
            $metadata[] = $item;
        }

        return $metadata;
    }

    private function getFieldValue(object|null $object, string $fieldName): mixed
    {
        if ($object === null) {
            return null;
        }

        $getter = 'get' . ucfirst($fieldName);
        if (method_exists($object, $getter)) {
            return $object->$getter();
        }

        if (method_exists($object, 'getObjectVar')) {
            return $object->getObjectVar($fieldName);
        }

        return null;
    }

    private function setFieldValue(object $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);
        if (method_exists($object, $setter)) {
            $object->$setter($value);

            return;
        }

        if (method_exists($object, 'setObjectVar')) {
            $object->setObjectVar($fieldName, $value);
        }
    }

    private function normalizeString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
