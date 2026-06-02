<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AutomaticImageLinker;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Family;
use Pimcore\Model\DataObject\Frame;
use Pimcore\Model\DataObject\Model as ModelObject;

final class AutomaticImageLinkerTest extends Unit
{
    public function testFrameFilenameUsesDefaultFrameFirstMatching(): void
    {
        $parsed = $this->parseFilename('ALP-OPT-01-BLK-front.jpg');

        self::assertSame([Frame::class, ModelObject::class, Family::class], $parsed['targetClasses']);
        self::assertContains('ALP-OPT-01-BLK', $parsed['codeCandidates']);
        self::assertContains('ALP-OPT-01', $parsed['codeCandidates']);
    }

    public function testModelPrefixStripsFAndTargetsModelOnly(): void
    {
        $parsed = $this->parseFilename('F-ALP-OPT-01-front.jpg');

        self::assertSame([ModelObject::class], $parsed['targetClasses']);
        self::assertContains('ALP-OPT-01', $parsed['codeCandidates']);
        self::assertNotContains('F-ALP-OPT-01', $parsed['codeCandidates']);
    }

    /**
     * @return array{field: string, codeCandidates: list<string>, targetClasses: list<class-string>}
     */
    private function parseFilename(string $filename): array
    {
        $reflection = new \ReflectionClass(AutomaticImageLinker::class);
        $linker = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('parseFilename');
        $method->setAccessible(true);

        return $method->invoke($linker, $filename);
    }
}
