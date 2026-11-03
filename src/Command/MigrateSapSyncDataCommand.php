<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SupplierTypeValidator;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\SupplierProcessDetails;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sap-sync:migrate-data',
    description: 'Backfill derivable SAP-sync data after rebuilding the Pimcore classes.',
)]
final class MigrateSapSyncDataCommand extends Command
{
    private const APPLY_BATCH_SIZE = 10;

    private const DRY_RUN_BATCH_SIZE = 50;

    private int $batchSize = self::DRY_RUN_BATCH_SIZE;

    /** @var array<string, int> */
    private array $counts = [];

    /** @var list<string> */
    private array $unresolved = [];

    public function __construct(
        private readonly SupplierTypeValidator $supplierTypeValidator,
        private readonly LongRunningHelper $longRunningHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Persist changes (the default is a dry run)')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum objects per class, for staged verification', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $limit = max(0, (int) $input->getOption('limit'));
        $this->batchSize = $apply ? self::APPLY_BATCH_SIZE : self::DRY_RUN_BATCH_SIZE;

        $io->title('SAP sync data migration');
        $io->note($apply ? 'APPLY mode: changed objects will be saved.' : 'DRY-RUN mode: no objects will be saved. Use --apply to persist.');

        try {
            $this->assertRebuiltSchema();
            // Reference indexes must always be complete even when the migrated
            // object listings are deliberately limited for staged verification.
            $processes = $this->loadProcesses(0);
            $colors = $this->loadColors(0);
            $supplierProcesses = $this->deriveSupplierProcessesFromLinks($processes, $limit);
            $this->migrateSuppliers($supplierProcesses, $apply, $limit);
            $this->migrateComponents($processes, $apply, $limit);
            $this->migrateModels($apply, $limit);
            $this->migrateFrames($colors, $apply, $limit);
            $this->updateModelMaterialTypes($apply, $limit);
            $this->updateFamilyLaunchDates($apply, $limit);
            $this->auditSupplierLinks($processes, $limit);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($this->counts as $name => $count) {
            $rows[] = [$name, $count];
        }
        $io->table(['Result', 'Count'], $rows);

        if ($this->unresolved !== []) {
            $io->warning(sprintf('%d values need manual mapping. No value was invented.', count($this->unresolved)));
            foreach (array_slice($this->unresolved, 0, 100) as $message) {
                $io->writeln(' - '.$message);
            }
            if (count($this->unresolved) > 100) {
                $io->writeln(sprintf(' - … %d more', count($this->unresolved) - 100));
            }
        }

        $io->success($apply ? 'Migration completed. It is safe to run the command again.' : 'Dry run completed. Review the report, then rerun with --apply.');

        return Command::SUCCESS;
    }

    private function assertRebuiltSchema(): void
    {
        $required = [
            ['Pimcore\\Model\\DataObject\\SAPComponent', 'setQuantity'],
            ['Pimcore\\Model\\DataObject\\SAPComponent', 'setProcess'],
            ['Pimcore\\Model\\DataObject\\Model', 'setLaunchYear'],
            ['Pimcore\\Model\\DataObject\\Frame', 'setMaterialType'],
        ];

        foreach ($required as [$class, $method]) {
            if (!class_exists($class) || !method_exists($class, $method)) {
                throw new \RuntimeException(sprintf(
                    'Generated class %s::%s() is unavailable. Run pimcore:deployment:classes-rebuild on staging first.',
                    $class,
                    $method,
                ));
            }
        }
    }

    /** @return array<string, AbstractObject> */
    private function loadProcesses(int $limit): array
    {
        $index = [];
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Process', $limit) as $process) {
            foreach ([$this->get($process, 'code'), $this->get($process, 'name')] as $value) {
                $key = $this->normalise((string) $value);
                if ($key !== '') {
                    $index[$key] = $process;
                }
            }
        }

        return $index;
    }

    /** @return array<string, AbstractObject> */
    private function loadColors(int $limit): array
    {
        $index = [];
        $duplicates = [];
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Color', $limit) as $color) {
            if ($this->normalise((string) $this->get($color, 'colorType')) !== 'metal-bi-color') {
                continue;
            }
            $code = $this->normalise((string) $this->get($color, 'code'));
            if ($code === '' || isset($duplicates[$code])) {
                continue;
            }
            if (isset($index[$code])) {
                unset($index[$code]);
                $duplicates[$code] = true;
                $this->unresolved[] = sprintf('Color code "%s" is duplicated; exact combiColor migration is unsafe.', $code);
                continue;
            }
            $index[$code] = $color;
        }

        return $index;
    }

    /** @param array<string, AbstractObject> $processes */
    private function migrateComponents(array $processes, bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\SAPComponent', $limit) as $component) {
            $changed = false;
            if ($this->get($component, 'quantity') === null) {
                $this->set($component, 'quantity', '1');
                $this->increment('component.quantity backfilled');
                $changed = true;
            }

            $process = $this->get($component, 'process');
            if (!$process instanceof AbstractObject) {
                $target = $this->componentProcessTarget($component);
                $process = $processes[$this->normalise($target)] ?? null;
                if ($process instanceof AbstractObject) {
                    $this->set($component, 'process', $process);
                    $this->increment('component.process backfilled');
                    $changed = true;
                } elseif ($target !== '') {
                    $this->unresolved[] = sprintf('SAPComponent #%d: no Process matches "%s".', $component->getId(), $target);
                } else {
                    $this->unresolved[] = sprintf(
                        'SAPComponent #%d: Process is empty and cannot be derived from itemType, legacy step, or D-/E- item code.',
                        $component->getId(),
                    );
                }
            }

            if ($process instanceof AbstractObject && !$this->get($component, 'itemGroup') instanceof AbstractObject) {
                $itemGroup = $this->get($process, 'itemGroup');
                if ($itemGroup instanceof AbstractObject) {
                    $this->set($component, 'itemGroup', $itemGroup);
                    $this->increment('component.itemGroup inherited from process');
                    $changed = true;
                }
            }

            if (!$this->get($component, 'pricelist') instanceof AbstractObject) {
                $supplier = $this->get($component, 'supplier');
                $priceList = $supplier instanceof AbstractObject ? $this->get($supplier, 'pricelist') : null;
                if ($priceList instanceof AbstractObject) {
                    $this->set($component, 'pricelist', $priceList);
                    $this->increment('component.pricelist inherited from supplier');
                    $changed = true;
                }
            }

            $supplierLinks = $this->get($component, 'suppliers');
            $legacySupplier = $this->get($component, 'supplier');
            if ((!is_array($supplierLinks) || $supplierLinks === []) && $legacySupplier instanceof AbstractObject) {
                $process = $this->get($component, 'process');
                $processCode = $process instanceof AbstractObject ? trim((string) $this->get($process, 'code')) : '';
                if ($processCode !== '') {
                    $metadata = new ObjectMetadata(
                        'suppliers',
                        ['supplierType', 'leadTime', 'workItemGroup'],
                        $legacySupplier,
                    );
                    $metadata->setSupplierType($processCode);
                    $this->set($component, 'suppliers', [$metadata]);
                    $this->increment('component supplier/process link migrated');
                    $changed = true;
                }
            }

            $this->saveIfChanged($component, $changed, $apply);
        }
    }

    /**
     * @param array<int, array<int, AbstractObject>> $derivedProcesses supplier id => process id => Process
     */
    private function migrateSuppliers(array $derivedProcesses, bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Supplier', $limit) as $supplier) {
            $changed = false;
            $collection = $this->get($supplier, 'processDetails');
            $items = [];
            $processIds = [];

            if ($collection instanceof Fieldcollection) {
                foreach ($collection as $item) {
                    if (!$item instanceof SupplierProcessDetails) {
                        continue;
                    }
                    $process = $item->getProcess();
                    if ($process instanceof AbstractObject) {
                        $processIds[$process->getId()] = true;
                    }
                    $items[] = $item;
                }
            }

            $processRelations = array_values(array_filter(
                is_array($this->get($supplier, 'process')) ? $this->get($supplier, 'process') : [],
                static fn (mixed $process): bool => $process instanceof AbstractObject,
            ));
            $relatedIds = array_fill_keys(array_map(
                static fn (AbstractObject $process): int => $process->getId(),
                $processRelations,
            ), true);
            foreach ($derivedProcesses[$supplier->getId()] ?? [] as $process) {
                if (isset($relatedIds[$process->getId()])) {
                    continue;
                }
                $processRelations[] = $process;
                $relatedIds[$process->getId()] = true;
                $this->increment('supplier.process derived from supplier links');
                $changed = true;
            }
            $priceList = $this->get($supplier, 'pricelist');
            foreach ($processRelations as $process) {
                if (isset($processIds[$process->getId()])) {
                    continue;
                }
                $detail = new SupplierProcessDetails();
                $detail->setProcess($process);
                if ($priceList instanceof AbstractObject) {
                    $detail->setPriceList($priceList);
                }
                $items[] = $detail;
                $processIds[$process->getId()] = true;
                $this->increment('supplier.processDetails created from process relation');
                $changed = true;
            }

            // Keep the original relation usable for existing consumers when a
            // populated processDetails collection contains additional Processes.
            foreach ($items as $item) {
                $process = $item->getProcess();
                if (!$process instanceof AbstractObject) {
                    continue;
                }
                $known = false;
                foreach ($processRelations as $relatedProcess) {
                    if ($relatedProcess->getId() === $process->getId()) {
                        $known = true;
                        break;
                    }
                }
                if (!$known) {
                    $processRelations[] = $process;
                    $this->increment('supplier.process relation restored from processDetails');
                    $changed = true;
                }
            }

            if ($changed) {
                $this->set($supplier, 'process', $processRelations);
                $this->set($supplier, 'processDetails', new Fieldcollection($items, 'processDetails'));
            }
            $this->saveIfChanged($supplier, $changed, $apply);
        }
    }

    /**
     * Existing supplier-link metadata is the authoritative migration source for
     * Suppliers that predate Process relations.
     *
     * @param array<string, AbstractObject> $processes
     * @return array<int, array<int, AbstractObject>> supplier id => process id => Process
     */
    private function deriveSupplierProcessesFromLinks(array $processes, int $limit): array
    {
        $derived = [];
        foreach (['Family', 'Model', 'Frame'] as $shortClass) {
            foreach ($this->objects('Pimcore\\Model\\DataObject\\'.$shortClass, $limit) as $object) {
                $links = $this->get($object, 'suppliers');
                foreach (is_array($links) ? $links : [] as $link) {
                    if (!$link instanceof ObjectMetadata) {
                        continue;
                    }
                    $supplier = $link->getObject();
                    if (!$supplier instanceof AbstractObject) {
                        continue;
                    }
                    $types = $link->getSupplierType();
                    $types = is_array($types) ? $types : (preg_split('/[,;]/', (string) $types) ?: []);
                    foreach ($types as $type) {
                        $key = $this->normalise((string) $type);
                        $process = $processes[$key] ?? null;
                        if ($process instanceof AbstractObject) {
                            $derived[$supplier->getId()][$process->getId()] = $process;
                        } elseif ($key !== '') {
                            $this->unresolved[] = sprintf(
                                '%s #%d supplier link: no Process matches supplier type "%s".',
                                $shortClass,
                                $object->getId(),
                                (string) $type,
                            );
                        }
                    }
                }
            }
        }

        foreach ($this->objects('Pimcore\\Model\\DataObject\\SAPComponent', $limit) as $component) {
            $supplier = $this->get($component, 'supplier');
            if (!$supplier instanceof AbstractObject) {
                continue;
            }
            $process = $this->get($component, 'process');
            if (!$process instanceof AbstractObject) {
                $target = $this->componentProcessTarget($component);
                $process = $processes[$this->normalise($target)] ?? null;
            }
            if ($process instanceof AbstractObject) {
                $derived[$supplier->getId()][$process->getId()] = $process;
            }
        }

        return $derived;
    }

    private function migrateModels(bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Model', $limit) as $model) {
            $parent = $model->getParent();
            $changed = false;
            foreach (['launchYear', 'launchPeriod'] as $field) {
                if ($this->isBlank($this->get($model, $field)) && $parent instanceof AbstractObject) {
                    $value = $this->get($parent, $field);
                    if (!$this->isBlank($value)) {
                        $this->set($model, $field, $value);
                        $this->increment('model.'.$field.' backfilled');
                        $changed = true;
                    }
                }
            }
            $this->saveIfChanged($model, $changed, $apply);
        }
    }

    /** @param array<string, AbstractObject> $colors */
    private function migrateFrames(array $colors, bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Frame', $limit) as $frame) {
            $changed = false;
            if ($this->isBlank($this->get($frame, 'materialType'))) {
                $parent = $frame->getParent();
                $value = $parent instanceof AbstractObject ? $this->get($parent, 'materialType') : null;
                if (!$this->isBlank($value)) {
                    $this->set($frame, 'materialType', $value);
                    $this->increment('frame.materialType backfilled');
                    $changed = true;
                }
            }
            if (!$this->get($frame, 'combiColor') instanceof AbstractObject) {
                $code = $this->normalise((string) $this->get($frame, 'mainColorCode'));
                if ($code !== '' && isset($colors[$code])) {
                    $this->set($frame, 'combiColor', $colors[$code]);
                    $this->increment('frame.combiColor backfilled');
                    $changed = true;
                }
            }
            $colorsChanged = $this->backfillComposedColorMetadata($frame);
            if ($colorsChanged) {
                $changed = true;
            }
            $this->saveIfChanged($frame, $changed, $apply);
        }
    }

    private function backfillComposedColorMetadata(AbstractObject $frame): bool
    {
        $metadata = $this->get($frame, 'composedColors');
        if (!is_array($metadata)) {
            return false;
        }

        $changed = false;
        foreach ($metadata as $index => $item) {
            if (!$item instanceof ObjectMetadata) {
                continue;
            }
            if ($item->getOrderNr() === null) {
                $item->setOrderNr($index + 1);
                $this->increment('frame.composedColors order backfilled');
                $changed = true;
            }
            if ($item->getQty() === null) {
                $color = $item->getObject();
                $colorType = $color instanceof AbstractObject ? $this->normalise((string) $this->get($color, 'colorType')) : '';
                $qty = match ($colorType) {
                    'acetate' => '0.01',
                    'metal-bi-color', 'metal-single-color' => '1',
                    default => null,
                };
                if ($qty !== null) {
                    $item->setQty($qty);
                    $this->increment('frame.composedColors quantity backfilled');
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->set($frame, 'composedColors', $metadata);
        }

        return $changed;
    }

    private function updateFamilyLaunchDates(bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Family', $limit) as $family) {
            $models = [];
            foreach ($family->getChildren() as $child) {
                if ($child instanceof AbstractObject && $child::class === 'Pimcore\\Model\\DataObject\\Model') {
                    $models[] = $child;
                }
            }

            $earliest = null;
            foreach ($models as $model) {
                $year = $this->get($model, 'launchYear');
                if (!is_numeric($year)) {
                    continue;
                }
                $period = (string) $this->get($model, 'launchPeriod');
                $candidate = [(int) $year, $this->launchPeriodOrder($period), $period];
                if ($earliest === null || [$candidate[0], $candidate[1]] < [$earliest[0], $earliest[1]]) {
                    $earliest = $candidate;
                }
            }
            if ($earliest === null) {
                continue;
            }

            $changed = false;
            if ((int) $this->get($family, 'launchYear') !== $earliest[0]) {
                $this->set($family, 'launchYear', (string) $earliest[0]);
                $this->increment('family.launchYear derived from models');
                $changed = true;
            }
            if ($earliest[2] !== '' && (string) $this->get($family, 'launchPeriod') !== $earliest[2]) {
                $this->set($family, 'launchPeriod', $earliest[2]);
                $this->increment('family.launchPeriod derived from models');
                $changed = true;
            }
            $this->saveIfChanged($family, $changed, $apply);
        }
    }

    private function updateModelMaterialTypes(bool $apply, int $limit): void
    {
        foreach ($this->objects('Pimcore\\Model\\DataObject\\Model', $limit) as $model) {
            $counts = [];
            foreach ($model->getChildren() as $child) {
                if (!$child instanceof AbstractObject || $child::class !== 'Pimcore\\Model\\DataObject\\Frame') {
                    continue;
                }
                $materialType = $this->normalise((string) $this->get($child, 'materialType'));
                if ($materialType !== '') {
                    $counts[$materialType] = ($counts[$materialType] ?? 0) + 1;
                }
            }
            if ($counts === []) {
                continue;
            }
            arsort($counts);
            $max = reset($counts);
            $majorities = array_keys(array_filter($counts, static fn (int $count): bool => $count === $max));
            // A tie has no majority and is intentionally left blank.
            $majority = count($majorities) === 1 ? $majorities[0] : '';
            if ((string) $this->get($model, 'materialType') === $majority) {
                continue;
            }
            $this->set($model, 'materialType', $majority);
            $this->increment($majority === '' ? 'model.materialType cleared (no majority)' : 'model.materialType derived from frames');
            $this->saveIfChanged($model, true, $apply);
        }
    }

    private function launchPeriodOrder(string $period): int
    {
        return match ($this->normalise($period)) {
            'q1', 'spring', 'ss' => 1,
            'q2' => 2,
            'q3', 'fall', 'autumn', 'fw', 'aw' => 3,
            'q4' => 4,
            default => 99,
        };
    }

    private function componentProcessTarget(AbstractObject $component): string
    {
        $types = $this->get($component, 'itemType');
        $types = is_array($types) ? array_map([$this, 'normalise'], $types) : [];
        if (in_array('brut', $types, true)) {
            return 'Metal';
        }
        if (in_array('acetate', $types, true)) {
            return 'Color';
        }

        $itemCode = trim((string) $this->get($component, 'itemCode'));
        if (preg_match('/^[DE](?:-|\b)/i', $itemCode) === 1) {
            return 'Finishing';
        }

        return match ($this->normalise((string) $this->get($component, 'step'))) {
            'color', 'acetate' => 'Color',
            'metal' => 'Metal',
            'parts', 'finishing' => 'Finishing',
            default => '',
        };
    }

    /** @param array<string, AbstractObject> $processes */
    private function auditSupplierLinks(array $processes, int $limit): void
    {
        foreach (['Family', 'Model', 'Frame'] as $shortClass) {
            foreach ($this->objects('Pimcore\\Model\\DataObject\\'.$shortClass, $limit) as $object) {
                $links = $this->get($object, 'suppliers');
                if (!is_array($links)) {
                    continue;
                }
                foreach ($this->supplierTypeValidator->violations($links) as $violation) {
                    $this->unresolved[] = sprintf('%s #%d supplier link: %s', $shortClass, $object->getId(), $violation);
                }
                foreach ($links as $link) {
                    if (!$link instanceof ObjectMetadata) {
                        continue;
                    }
                    $types = $link->getSupplierType();
                    $types = is_array($types) ? $types : [$types];
                    foreach ($types as $type) {
                        $target = match ($this->normalise((string) $type)) {
                            'color', 'acetate' => 'Color',
                            'metal' => 'Metal',
                            'parts', 'finishing' => 'Finishing',
                            default => (string) $type,
                        };
                        if ($target !== '' && !isset($processes[$this->normalise($target)])) {
                            $this->unresolved[] = sprintf(
                                '%s #%d supplier link: no Process matches supplier type "%s".',
                                $shortClass,
                                $object->getId(),
                                (string) $type,
                            );
                        }
                    }
                }
            }
        }
    }

    /** @return iterable<AbstractObject> */
    private function objects(string $class, int $limit): iterable
    {
        $listingClass = $class.'\\Listing';
        if (!class_exists($listingClass)) {
            throw new \RuntimeException(sprintf('Listing class %s is unavailable after the class rebuild.', $listingClass));
        }

        $offset = 0;
        $remaining = $limit > 0 ? $limit : null;
        do {
            $batchSize = $remaining === null ? $this->batchSize : min($this->batchSize, $remaining);
            if ($batchSize === 0) {
                break;
            }

            $listing = new $listingClass();
            $listing->setUnpublished(true);
            $listing->setOrderKey('id');
            $listing->setOrder('ASC');
            $listing->setOffset($offset);
            $listing->setLimit($batchSize);
            $objects = $listing->load();
            $loaded = count($objects);

            foreach ($objects as $object) {
                if ($object instanceof AbstractObject) {
                    yield $object;
                }
            }

            $offset += $loaded;
            if ($remaining !== null) {
                $remaining -= $loaded;
            }
            unset($objects, $listing);
            $this->longRunningHelper->cleanUp();
        } while ($loaded === $batchSize && ($remaining === null || $remaining > 0));
    }

    private function get(AbstractObject $object, string $field): mixed
    {
        $getter = 'get'.ucfirst($field);

        return method_exists($object, $getter) ? $object->{$getter}() : null;
    }

    private function set(AbstractObject $object, string $field, mixed $value): void
    {
        $setter = 'set'.ucfirst($field);
        if (!method_exists($object, $setter)) {
            throw new \RuntimeException(sprintf('%s::%s() is missing; rebuild the classes before migration.', $object::class, $setter));
        }
        $object->{$setter}($value);
    }

    private function saveIfChanged(AbstractObject $object, bool $changed, bool $apply): void
    {
        if ($changed && $apply) {
            $object->save();
            $this->increment('objects saved');
        }
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function normalise(string $value): string
    {
        return strtolower(trim($value));
    }

    private function increment(string $key): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }
}
