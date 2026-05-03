<?php declare(strict_types=1);

namespace App\Controller\Admin;

use App\EventSubscriber\QualityControlSubscriber;
use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/quality-remarks-portlet')]
final class QualityRemarksPortletController extends AdminAbstractController
{
    private const REMARKS_GETTER = 'getQualityControlRemarks';
    private const DEFAULT_LIMIT = 500;
    private const MAX_LIMIT = 2000;

    /**
     * @var array<string, array{label: string, listingClass: class-string}>
     */
    private const OBJECT_TYPES = [
        'family' => [
            'label' => 'Family',
            'listingClass' => '\\Pimcore\\Model\\DataObject\\Family\\Listing',
        ],
        'model' => [
            'label' => 'Model',
            'listingClass' => '\\Pimcore\\Model\\DataObject\\Model\\Listing',
        ],
        'frame' => [
            'label' => 'Frame',
            'listingClass' => '\\Pimcore\\Model\\DataObject\\Frame\\Listing',
        ],
    ];

    /**
     * @var array<string, list<string|int>>
     */
    private const REMARK_COLUMN_KEYS = [
        'createdAt' => ['createdAt', 'date', 'Date', 0],
        'createdBy' => ['createdBy', 'by', 'By', 1],
        'type' => ['type', 'Type', 2],
        'status' => ['status', 'Status', 3],
        'remark' => ['remark', 'remarks', 'Remark', 4],
    ];

    #[Route('/data', name: 'app_admin_quality_remarks_portlet_data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        $this->assertQualityControlDashboardAccess();

        $filters = $this->readFilters($request);
        $rows = [];
        $warnings = [];

        foreach (self::OBJECT_TYPES as $objectType => $definition) {
            if ($filters['objectType'] !== 'all' && $filters['objectType'] !== $objectType) {
                continue;
            }

            foreach ($this->loadRowsForObjectType($objectType, $definition, $filters, $warnings) as $row) {
                $rows[] = $row;
            }
        }

        usort($rows, $this->compareRemarkRows(...));

        $total = count($rows);
        $limitedRows = array_slice($rows, 0, $filters['limit']);

        return $this->adminJson([
            'success' => true,
            'rows' => $limitedRows,
            'total' => $total,
            'truncated' => $total > count($limitedRows),
            'warnings' => $warnings,
        ]);
    }

    private function assertQualityControlDashboardAccess(): void
    {
        $user = $this->getAdminUser();
        $isAdmin = $user && (
            (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (method_exists($user, 'getAdmin') && $user->getAdmin())
        );
        $canUseDashboards = $user && method_exists($user, 'isAllowed') && $user->isAllowed('dashboards');
        $canUseQualityControl = $user && method_exists($user, 'isAllowed') && $user->isAllowed(QualityControlSubscriber::PERMISSION_KEY);

        if (!$isAdmin && (!$canUseDashboards || !$canUseQualityControl)) {
            throw $this->createAccessDeniedException('Not allowed');
        }
    }

    /**
     * @param array{objectType: string, code: string, type: string, status: string, createdBy: string, remark: string, limit: int} $filters
     * @param array<int, string> $warnings
     * @param array{label: string, listingClass: class-string} $definition
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadRowsForObjectType(string $objectType, array $definition, array $filters, array &$warnings): array
    {
        $listingClass = $definition['listingClass'];
        if (!class_exists($listingClass)) {
            $warnings[] = sprintf('%s listing class is not available.', $definition['label']);

            return [];
        }

        $listing = new $listingClass();
        $listing->setUnpublished(true);
        $listing->setOrderKey('oo_id');
        $listing->setOrder('DESC');

        try {
            $objects = $listing->load();
        } catch (\Throwable $exception) {
            $warnings[] = sprintf('%s objects could not be loaded: %s', $definition['label'], $exception->getMessage());

            return [];
        }

        $rows = [];
        foreach ($objects as $object) {
            if (!$object instanceof AbstractObject || !$object->isAllowed('view')) {
                continue;
            }

            $objectRows = $this->buildRowsForObject($object, $objectType, $definition['label']);
            foreach ($objectRows as $row) {
                if ($this->matchesFilters($row, $filters)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForObject(AbstractObject $object, string $objectType, string $objectTypeLabel): array
    {
        if (!method_exists($object, self::REMARKS_GETTER)) {
            return [];
        }

        try {
            $remarks = $object->{self::REMARKS_GETTER}();
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($remarks)) {
            return [];
        }

        $objectPayload = $this->objectPayload($object, $objectType, $objectTypeLabel);
        $rows = [];

        foreach ($remarks as $index => $remark) {
            if (!is_array($remark)) {
                continue;
            }

            $remarkPayload = $this->remarkPayload($remark);
            if (!$this->hasRemarkContent($remarkPayload)) {
                continue;
            }

            $rows[] = $objectPayload + $remarkPayload + [
                'rowIndex' => is_int($index) ? $index : count($rows),
            ];
        }

        return $rows;
    }

    /**
     * @return array{objectId: int, objectType: string, objectTypeLabel: string, code: string, name: string, label: string, path: string}
     */
    private function objectPayload(AbstractObject $object, string $objectType, string $objectTypeLabel): array
    {
        $code = $this->readString($object, 'getCode');
        $name = $this->readString($object, 'getName');

        return [
            'objectId' => $object->getId(),
            'objectType' => $objectType,
            'objectTypeLabel' => $objectTypeLabel,
            'code' => $code,
            'name' => $name,
            'label' => $this->formatObjectLabel($object, $code, $name),
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

    /**
     * @param array<string|int, mixed> $remark
     *
     * @return array{createdAt: string, createdBy: string, type: string, status: string, remark: string}
     */
    private function remarkPayload(array $remark): array
    {
        return [
            'createdAt' => $this->readRemarkCell($remark, 'createdAt'),
            'createdBy' => $this->readRemarkCell($remark, 'createdBy'),
            'type' => $this->readRemarkCell($remark, 'type'),
            'status' => $this->readRemarkCell($remark, 'status'),
            'remark' => $this->readRemarkCell($remark, 'remark'),
        ];
    }

    /**
     * @param array<string|int, mixed> $remark
     */
    private function readRemarkCell(array $remark, string $column): string
    {
        foreach (self::REMARK_COLUMN_KEYS[$column] as $key) {
            if (!array_key_exists($key, $remark)) {
                continue;
            }

            return $this->normalizeCellValue($remark[$key]);
        }

        return '';
    }

    private function normalizeCellValue(mixed $value): string
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
     * @param array{createdAt: string, createdBy: string, type: string, status: string, remark: string} $remark
     */
    private function hasRemarkContent(array $remark): bool
    {
        foreach ($remark as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     * @param array{objectType: string, code: string, type: string, status: string, createdBy: string, remark: string, limit: int} $filters
     */
    private function matchesFilters(array $row, array $filters): bool
    {
        if ($filters['code'] !== '' && !$this->containsAny($filters['code'], [
            $row['code'] ?? '',
            $row['name'] ?? '',
            $row['label'] ?? '',
            $row['path'] ?? '',
            $row['objectId'] ?? '',
        ])) {
            return false;
        }

        foreach (['type', 'status', 'createdBy', 'remark'] as $filterName) {
            if ($filters[$filterName] === '') {
                continue;
            }

            if (!$this->contains((string) ($row[$filterName] ?? ''), $filters[$filterName])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function containsAny(string $needle, array $values): bool
    {
        foreach ($values as $value) {
            if ($this->contains((string) $value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function contains(string $haystack, string $needle): bool
    {
        return str_contains(strtolower($haystack), strtolower($needle));
    }

    private function readString(AbstractObject $object, string $getter): string
    {
        if (!method_exists($object, $getter)) {
            return '';
        }

        return $this->normalizeCellValue($object->{$getter}());
    }

    /**
     * @return array{objectType: string, code: string, type: string, status: string, createdBy: string, remark: string, limit: int}
     */
    private function readFilters(Request $request): array
    {
        $objectType = strtolower(trim((string) $request->query->get('objectType', 'all')));
        if ($objectType === '' || ($objectType !== 'all' && !isset(self::OBJECT_TYPES[$objectType]))) {
            $objectType = 'all';
        }

        return [
            'objectType' => $objectType,
            'code' => $this->readQueryString($request, 'code'),
            'type' => $this->readQueryString($request, 'type'),
            'status' => $this->readQueryString($request, 'status'),
            'createdBy' => $this->readQueryString($request, 'createdBy'),
            'remark' => $this->readQueryString($request, 'remark'),
            'limit' => $this->readLimit($request),
        ];
    }

    private function readQueryString(Request $request, string $key): string
    {
        return trim((string) $request->query->get($key, ''));
    }

    private function readLimit(Request $request): int
    {
        $limit = (int) $request->query->get('limit', self::DEFAULT_LIMIT);

        return max(1, min(self::MAX_LIMIT, $limit > 0 ? $limit : self::DEFAULT_LIMIT));
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareRemarkRows(array $left, array $right): int
    {
        $timestampComparison = $this->timestamp((string) ($right['createdAt'] ?? ''))
            <=> $this->timestamp((string) ($left['createdAt'] ?? ''));

        if ($timestampComparison !== 0) {
            return $timestampComparison;
        }

        $labelComparison = strnatcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        if ($labelComparison !== 0) {
            return $labelComparison;
        }

        return ((int) ($right['rowIndex'] ?? 0)) <=> ((int) ($left['rowIndex'] ?? 0));
    }

    private function timestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $timestamp = strtotime($value);

        return is_int($timestamp) ? $timestamp : 0;
    }
}
