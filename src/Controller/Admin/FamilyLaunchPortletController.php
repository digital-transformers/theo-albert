<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/family-launch-portlet')]
final class FamilyLaunchPortletController extends AdminAbstractController
{
    private const FAMILY_LISTING_CLASS = '\\Pimcore\\Model\\DataObject\\Family\\Listing';
    private const MODEL_CLASS_NAME = 'model';

    private const FALLBACK_PERIODS = [
        'jan' => 'January',
        'apr' => 'April',
        'sum' => 'Summer',
        'silmo' => 'Silmo',
    ];

    #[Route('/data', name: 'app_admin_family_launch_portlet_data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $this->assertDashboardAccess();

        $periods = $this->getLaunchPeriods();
        $currentYear = (int) (new \DateTimeImmutable())->format('Y');
        $years = range($currentYear - 1, $currentYear + 3);
        $rowsByYear = $this->buildEmptyRows($years, array_keys($periods));

        $listingClass = self::FAMILY_LISTING_CLASS;
        if (!class_exists($listingClass)) {
            return $this->adminJson([
                'success' => false,
                'message' => 'Family listing class is not available.',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        /** @var DataObject\Listing $familyList */
        $familyList = new $listingClass();
        $familyList->setUnpublished(true);
        $familyList->setCondition(
            'launchYear >= ? AND launchYear <= ? AND launchPeriod IN (?)',
            [min($years), max($years), array_keys($periods)]
        );
        $familyList->setOrderKey(['launchYear', 'launchPeriod', 'code']);
        $familyList->setOrder(['ASC', 'ASC', 'ASC']);

        foreach ($familyList->load() as $family) {
            if (!$family instanceof AbstractObject || !$family->isAllowed('view')) {
                continue;
            }

            $launchYear = $this->readInteger($family, 'getLaunchYear');
            $launchPeriod = $this->readString($family, 'getLaunchPeriod');

            if ($launchYear === null || !isset($rowsByYear[$launchYear][$launchPeriod])) {
                continue;
            }

            $payload = $this->objectPayload($family);
            $payload['models'] = $this->getFamilyModels($family);

            $rowsByYear[$launchYear][$launchPeriod][] = $payload;
        }

        foreach ($rowsByYear as &$row) {
            foreach (array_keys($periods) as $period) {
                usort($row[$period], $this->compareByLabel(...));
            }
        }
        unset($row);

        return $this->adminJson([
            'success' => true,
            'currentYear' => $currentYear,
            'periods' => $this->formatPeriods($periods),
            'rows' => array_values($rowsByYear),
        ]);
    }

    private function assertDashboardAccess(): void
    {
        $user = $this->getAdminUser();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        $canUseDashboards = $user && method_exists($user, 'isAllowed') && $user->isAllowed('dashboards');

        if (!$isAdmin && !$canUseDashboards) {
            throw $this->createAccessDeniedException('Not allowed');
        }
    }

    /**
     * @param int[] $years
     * @param string[] $periods
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildEmptyRows(array $years, array $periods): array
    {
        $rows = [];
        foreach ($years as $year) {
            $row = ['year' => $year];
            foreach ($periods as $period) {
                $row[$period] = [];
            }
            $rows[$year] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function getLaunchPeriods(): array
    {
        $definition = ClassDefinition::getByName('family');
        $field = $definition?->getFieldDefinition('launchPeriod');

        if (!is_object($field) || !method_exists($field, 'getOptions')) {
            return self::FALLBACK_PERIODS;
        }

        $periods = [];
        foreach ($field->getOptions() as $option) {
            $value = trim((string) ($option['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $label = trim((string) ($option['key'] ?? ''));
            $periods[$value] = $label !== '' ? $label : $value;
        }

        return $periods ?: self::FALLBACK_PERIODS;
    }

    /**
     * @param array<string, string> $periods
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function formatPeriods(array $periods): array
    {
        $formatted = [];
        foreach ($periods as $value => $label) {
            $formatted[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $formatted;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFamilyModels(AbstractObject $family): array
    {
        $models = [];
        $children = $family->getChildren([DataObject::OBJECT_TYPE_OBJECT], true);

        foreach ($children as $child) {
            if (!$child instanceof Concrete || strtolower((string) $child->getClassName()) !== self::MODEL_CLASS_NAME) {
                continue;
            }

            if (!$child->isAllowed('view')) {
                continue;
            }

            $models[] = $this->objectPayload($child);
        }

        usort($models, $this->compareByLabel(...));

        return $models;
    }

    /**
     * @return array{id: int, code: string, name: string, label: string, path: string}
     */
    private function objectPayload(AbstractObject $object): array
    {
        $code = $this->readString($object, 'getCode');
        $name = $this->readString($object, 'getName');
        $label = $this->formatObjectLabel($object, $code, $name);

        return [
            'id' => $object->getId(),
            'code' => $code,
            'name' => $name,
            'label' => $label,
            'path' => $object->getRealFullPath(),
        ];
    }

    private function formatObjectLabel(AbstractObject $object, string $code, string $name): string
    {
        if ($code !== '' && $name !== '') {
            return $code . ' - ' . $name;
        }

        if ($code !== '') {
            return $code;
        }

        if ($name !== '') {
            return $name;
        }

        return '#' . $object->getId();
    }

    private function readString(AbstractObject $object, string $getter): string
    {
        if (!method_exists($object, $getter)) {
            return '';
        }

        $value = $object->{$getter}();
        if ($value === null) {
            return '';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return trim((string) $value);
        }

        return '';
    }

    private function readInteger(AbstractObject $object, string $getter): ?int
    {
        $value = $this->readString($object, $getter);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array{id?: int, label?: string} $left
     * @param array{id?: int, label?: string} $right
     */
    private function compareByLabel(array $left, array $right): int
    {
        $labelComparison = strnatcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));

        return $labelComparison !== 0
            ? $labelComparison
            : ((int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0));
    }
}
