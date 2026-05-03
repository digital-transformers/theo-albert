<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use Pimcore\Bundle\AdminBundle\Event\AdminEvents;
use Pimcore\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\AdminStyle;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class QualityControlTreeIndicatorSubscriber implements EventSubscriberInterface
{
    private const REMARKS_GETTER = 'getQualityControlRemarks';
    private const TREE_CLASS = 'quality-control-tree-has-remarks';
    private const SUPPORTED_CLASS_NAMES = ['family', 'model', 'frame'];

    private const REMARK_COLUMNS = [
        'createdAt',
        'createdBy',
        'type',
        'status',
        'remark',
    ];

    public function __construct(
        private TokenStorageUserResolver $userResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::RESOLVE_ELEMENT_ADMIN_STYLE => 'onResolveElementAdminStyle',
        ];
    }

    public function onResolveElementAdminStyle(ElementAdminStyleEvent $event): void
    {
        if ($event->getContext() !== ElementAdminStyleEvent::CONTEXT_TREE) {
            return;
        }

        if (!$this->userCanAccessQualityControl()) {
            return;
        }

        $object = $event->getElement();
        if (!$object instanceof Concrete || !$this->supportsObject($object) || !$this->hasQualityRemarks($object)) {
            return;
        }

        $adminStyle = $event->getAdminStyle();
        $adminStyle->appendElementCssClass(self::TREE_CLASS);
        $adminStyle->setElementQtipConfig($this->appendTooltip($adminStyle));
    }

    private function userCanAccessQualityControl(): bool
    {
        $user = $this->userResolver->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $user->getAdmin() || $user->isAllowed(QualityControlSubscriber::PERMISSION_KEY);
    }

    private function supportsObject(Concrete $object): bool
    {
        return in_array(strtolower((string) $object->getClassName()), self::SUPPORTED_CLASS_NAMES, true);
    }

    private function hasQualityRemarks(Concrete $object): bool
    {
        if (!method_exists($object, self::REMARKS_GETTER)) {
            return false;
        }

        try {
            $rows = $object->{self::REMARKS_GETTER}();
        } catch (\Throwable) {
            return false;
        }

        if (!is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (is_array($row) && $this->rowHasContent($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string|int, mixed> $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach (self::REMARK_COLUMNS as $index => $columnName) {
            $value = $row[$columnName] ?? $row[$index] ?? null;
            if ($this->normalizeValue($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @return array{title?: string, text?: string}
     */
    private function appendTooltip(AdminStyle $adminStyle): array
    {
        $tooltip = $adminStyle->getElementQtipConfig() ?? [];
        $existingText = trim((string) ($tooltip['text'] ?? ''));
        $message = 'Quality remarks present';

        $tooltip['text'] = $existingText !== ''
            ? $existingText . '<br>' . $message
            : $message;

        return $tooltip;
    }
}
