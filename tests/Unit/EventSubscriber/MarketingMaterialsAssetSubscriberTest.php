<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\MarketingMaterialsAssetSubscriber;
use Codeception\Test\Unit;
use Pimcore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class MarketingMaterialsAssetSubscriberTest extends Unit
{
    protected function _before(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('event_dispatcher')
            ->willReturn($dispatcher);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);

        Pimcore::setKernel($kernel);
    }

    public function testBuildHierarchySegmentsIncludesFamilyNameModelAndFrame(): void
    {
        $subscriber = new MarketingMaterialsAssetSubscriber();
        $family = (new MarketingMaterialsTestObject())
            ->setClassName('family')
            ->setCode('TA-ALPHA')
            ->setName('Alpha');
        $model = (new MarketingMaterialsTestObject())
            ->setClassName('model')
            ->setCode('ALP-OPT-01')
            ->setTestParent($family);
        $frame = (new MarketingMaterialsTestObject())
            ->setClassName('frame')
            ->setCode('ALP-OPT-01-001')
            ->setTestParent($model);

        $method = new \ReflectionMethod($subscriber, 'buildHierarchySegments');
        $method->setAccessible(true);

        self::assertSame(
            ['TA-ALPHA - Alpha', 'ALP-OPT-01', 'ALP-OPT-01-001'],
            $method->invoke($subscriber, $frame)
        );
    }

    public function testBuildHierarchySegmentsRequiresFamilyForModel(): void
    {
        $subscriber = new MarketingMaterialsAssetSubscriber();
        $model = (new MarketingMaterialsTestObject())
            ->setClassName('model')
            ->setCode('ALP-OPT-01');

        $method = new \ReflectionMethod($subscriber, 'buildHierarchySegments');
        $method->setAccessible(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Place the model below a family before saving Marketing materials assets.');

        $method->invoke($subscriber, $model);
    }
}

final class MarketingMaterialsTestObject extends Concrete
{
    private ?string $code = null;
    private ?string $name = null;
    private ?self $testParent = null;

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

    public function getParent(): ?self
    {
        return $this->testParent;
    }

    public function setTestParent(?self $testParent): static
    {
        $this->testParent = $testParent;

        return $this;
    }
}
