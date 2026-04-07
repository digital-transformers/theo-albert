<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ObjectKeyAutofillSubscriber;
use Codeception\Test\Unit;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;

final class ObjectKeyAutofillSubscriberTest extends Unit
{
    private ObjectKeyAutofillSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new ObjectKeyAutofillSubscriber();
    }

    public function testFamilyCodeAndNameAreFilledFromMatchingKey(): void
    {
        $object = new ObjectKeyAutofillTestObject();
        $object->setClassName('family');
        $object->setKey('FAM-12345 - Test Family');

        $this->subscriber->onPreAdd(new DataObjectEvent($object));

        self::assertSame('FAM-12345', $object->getCode());
        self::assertSame('Test Family', $object->getName());
    }

    public function testModelCodeAndNameAreFilledFromMatchingKey(): void
    {
        $object = new ObjectKeyAutofillTestObject();
        $object->setClassName('model');
        $object->setKey('MOD-001 - Test Model');

        $this->subscriber->onPreAdd(new DataObjectEvent($object));

        self::assertSame('MOD-001', $object->getCode());
        self::assertSame('Test Model', $object->getName());
    }

    public function testFrameCodeIsFilledFromKey(): void
    {
        $object = new ObjectKeyAutofillTestObject();
        $object->setClassName('frame');
        $object->setKey('FRAME-001');

        $this->subscriber->onPreAdd(new DataObjectEvent($object));

        self::assertSame('FRAME-001', $object->getCode());
        self::assertNull($object->getName());
    }

    public function testExistingValuesAreNotOverwritten(): void
    {
        $object = new ObjectKeyAutofillTestObject();
        $object->setClassName('family');
        $object->setKey('12345 - Test Family');
        $object->setCode('existing-code');
        $object->setName('Existing Name');

        $this->subscriber->onPreAdd(new DataObjectEvent($object));

        self::assertSame('existing-code', $object->getCode());
        self::assertSame('Existing Name', $object->getName());
    }

    public function testNonMatchingFamilyKeyIsIgnored(): void
    {
        $object = new ObjectKeyAutofillTestObject();
        $object->setClassName('family');
        $object->setKey('Test Family');

        $this->subscriber->onPreAdd(new DataObjectEvent($object));

        self::assertNull($object->getCode());
        self::assertNull($object->getName());
    }
}

final class ObjectKeyAutofillTestObject extends Concrete
{
    private ?string $code = null;
    private ?string $name = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
