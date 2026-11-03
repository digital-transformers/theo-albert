<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SupplierTypeValidator;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Process;
use Pimcore\Model\DataObject\Supplier;

final class SupplierTypeValidatorTest extends Unit
{
    public function testSelectedTypesMustBelongToSupplierProcesses(): void
    {
        $metal = new class extends Process {
            public function getCode(): ?string { return 'Metal'; }
        };
        $supplier = new class($metal) extends Supplier {
            public function __construct(private readonly Process $relatedProcess) {}
            public function getCode(): ?string { return 'L00001'; }
            public function getProcess(): array { return [$this->relatedProcess]; }
            public function getProcessDetails(): ?Fieldcollection { return null; }
        };

        $valid = $this->metadata($supplier, ['metal']);
        $invalid = $this->metadata($supplier, ['Color']);
        $validator = new SupplierTypeValidator();

        self::assertSame([], $validator->violations([$valid]));
        self::assertSame(
            ['Supplier L00001 does not have Process "Color". Allowed Processes: Metal.'],
            $validator->violations([$invalid]),
        );
    }

    /** @param list<string> $types */
    private function metadata(Supplier $supplier, array $types): ObjectMetadata
    {
        return new class($supplier, $types) extends ObjectMetadata {
            /** @param list<string> $types */
            public function __construct(private readonly Supplier $supplier, private readonly array $types)
            {
                parent::__construct('suppliers', ['supplierType']);
            }
            public function getObject(): ?Concrete { return $this->supplier; }
            /** @return list<string> */
            public function getSupplierType(): array { return $this->types; }
        };
    }
}
