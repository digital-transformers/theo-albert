<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SAPComponentImportValueNormalizerSubscriber implements EventSubscriberInterface
{
    private const TYPE_VALUES = ['detail', 'eartip', 'tens', 'other'];

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::PRE_ADD => 'onPreSave',
            DataObjectEvents::PRE_UPDATE => 'onPreSave',
        ];
    }

    public function onPreSave(DataObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof Concrete || $object->getClassName() !== 'SAPComponent') {
            return;
        }

        $this->normalizeItemGroup($object);
        $this->normalizeRelation($object, 'process', 'process', 'code');
        $this->normalizeRelation($object, 'supplier', 'supplier', 'code');
        $this->normalizeLinkedColor($object);
        $this->normalizeItemType($object);
    }

    private function normalizeItemGroup(Concrete $object): void
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('itemGroup');
        $rawValue = $object->getObjectVar('itemGroup');

        if ($rawValue instanceof AbstractObject || $this->isObjectList($rawValue)) {
            return;
        }

        $groups = $this->loadObjectsByAttribute('itemGroup', 'groupNum', $this->extractValues($rawValue));

        match ($fieldDefinition?->getFieldtype()) {
            'manyToManyObjectRelation' => $this->assignField($object, 'itemGroup', $groups),
            'manyToOneRelation' => $this->assignField($object, 'itemGroup', $groups[0] ?? null),
            default => null,
        };
    }

    private function normalizeRelation(Concrete $object, string $fieldName, string $classId, string $attributeName): void
    {
        $rawValue = $object->getObjectVar($fieldName);

        if ($rawValue instanceof AbstractObject || $this->isObjectList($rawValue)) {
            return;
        }

        $values = $this->extractValues($rawValue);
        if ($values === []) {
            return;
        }

        $this->assignField($object, $fieldName, $this->loadObjectByAttribute($classId, $attributeName, $values[0]));
    }

    private function normalizeLinkedColor(Concrete $object): void
    {
        $rawValue = $object->getObjectVar('linkedColor');
        if ($rawValue === null || $rawValue === '') {
            return;
        }

        $colorCode = $this->extractColorCode((string) $rawValue);
        if ($colorCode === '') {
            return;
        }

        $color = $this->loadObjectByAttribute('color', 'code', $colorCode);
        if ($color instanceof AbstractObject) {
            $this->assignField($object, 'linkedColor', (string) $color->getId());
        }
    }

    private function normalizeItemType(Concrete $object): void
    {
        $rawValue = $object->getObjectVar('itemType');

        if (is_array($rawValue)) {
            return;
        }

        $values = array_values(array_intersect($this->extractValues($rawValue), self::TYPE_VALUES));
        $this->assignField($object, 'itemType', $values);
    }

    /**
     * @return list<AbstractObject>
     */
    private function loadObjectsByAttribute(string $classId, string $attributeName, array $values): array
    {
        $objects = [];

        foreach ($values as $value) {
            $object = $this->loadObjectByAttribute($classId, $attributeName, $value);
            if ($object instanceof AbstractObject) {
                $objects[] = $object;
            }
        }

        return $objects;
    }

    private function loadObjectByAttribute(string $classId, string $attributeName, string $value): ?AbstractObject
    {
        if ($value === '') {
            return null;
        }

        $classDefinition = ClassDefinition::getById($classId);
        if (!$classDefinition instanceof ClassDefinition) {
            return null;
        }

        $listingClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName()) . '\\Listing';
        if (!class_exists($listingClass)) {
            return null;
        }

        $listing = new $listingClass();
        if (method_exists($listing, 'setUnpublished')) {
            $listing->setUnpublished(true);
        }

        $listing->setCondition(sprintf('`%s` = ?', $attributeName), [$value]);
        $listing->setLimit(1);

        $objects = $listing->load();

        return $objects[0] ?? null;
    }

    /**
     * @return list<string>
     */
    private function extractValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $values = [];
            array_walk_recursive($value, static function (mixed $item) use (&$values): void {
                if ($item !== null && $item !== '') {
                    $values[] = (string) $item;
                }
            });
        } else {
            $values = explode(';', (string) $value);
        }

        return array_values(array_filter(array_map('trim', $values), static fn (string $value): bool => $value !== ''));
    }

    private function extractColorCode(string $value): string
    {
        if (preg_match('/^\s*(.+?)\s+-\s+.+$/', $value, $matches)) {
            return trim($matches[1]);
        }

        return trim($value);
    }

    private function assignField(Concrete $object, string $fieldName, mixed $value): void
    {
        $setter = 'set' . ucfirst($fieldName);

        if (method_exists($object, $setter)) {
            $object->$setter($value);

            return;
        }

        $object->setObjectVar($fieldName, $value);
    }

    private function isObjectList(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!$item instanceof AbstractObject) {
                return false;
            }
        }

        return $value !== [];
    }
}
