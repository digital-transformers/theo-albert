<?php
declare(strict_types=1);

namespace App\Tests\Unit\DataObject;

use Codeception\Test\Unit;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;

final class FrameClassDefinitionTest extends Unit
{
    public function testFrameHasEANCodeInput(): void
    {
        $definition = require dirname(__DIR__, 3) . '/var/classes/definition_frame.php';
        $field = $definition->getFieldDefinition('EANCode');

        self::assertInstanceOf(Input::class, $field);
        self::assertSame('EAN Code', $field->getTitle());
        self::assertSame(190, $field->getColumnLength());
    }
}
