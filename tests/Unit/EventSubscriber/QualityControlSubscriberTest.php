<?php
declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\QualityControlSubscriber;
use Codeception\Test\Unit;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Symfony\Component\EventDispatcher\GenericEvent;

final class QualityControlSubscriberTest extends Unit
{
    public function testOnPreSendDataRemovesQualityControlTabAndFieldsWithoutPermission(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('restricted-user')
                ->setPermissions([])
                ->setAdmin(false)
        );

        $subscriber = new QualityControlSubscriber($resolver);
        $object = (new QualityControlTestObject())
            ->setClassName('family')
            ->setCode('FAM-001');

        $layout = new QualityControlTestLayout('root', [
            new QualityControlTestLayout('baseData'),
            new QualityControlTestLayout('qualityControl'),
        ]);

        $event = new GenericEvent(null, [
            'object' => $object,
            'data' => [
                'layout' => $layout,
                'data' => [
                    'qualityControlTargetFolder' => '/Quality Control/FAM-001',
                    'qualityControlRemarks' => [['remark' => 'Hidden']],
                    'name' => 'Visible',
                ],
                'metaData' => [
                    'qualityControlDocuments' => ['hidden'],
                    'name' => ['visible'],
                ],
            ],
        ]);

        $subscriber->onPreSendData($event);

        $data = $event->getArgument('data');
        self::assertSame(['baseData'], array_map(
            static fn (QualityControlTestLayout $child): string => $child->getName(),
            $layout->getChildren()
        ));
        self::assertArrayNotHasKey('qualityControlTargetFolder', $data['data']);
        self::assertArrayNotHasKey('qualityControlRemarks', $data['data']);
        self::assertArrayNotHasKey('qualityControlDocuments', $data['metaData']);
        self::assertSame('Visible', $data['data']['name']);
        self::assertSame(['visible'], $data['metaData']['name']);
    }

    public function testRemoveQualityControlLayoutRemovesNestedQualityControlPanel(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(null);

        $subscriber = new QualityControlSubscriber($resolver);
        $layout = new QualityControlTestLayout('root', [
            new QualityControlTestLayout('baseData'),
            new QualityControlTestLayout('wrapper', [
                new QualityControlTestLayout('qualityControl'),
                new QualityControlTestLayout('marketing'),
            ]),
        ]);

        $method = new \ReflectionMethod($subscriber, 'removeQualityControlLayout');
        $method->setAccessible(true);

        $method->invoke($subscriber, $layout);

        self::assertSame(['baseData', 'wrapper'], array_map(
            static fn (QualityControlTestLayout $child): string => $child->getName(),
            $layout->getChildren()
        ));
        self::assertSame(['marketing'], array_map(
            static fn (QualityControlTestLayout $child): string => $child->getName(),
            $layout->getChildren()[1]->getChildren()
        ));
    }

    public function testResolveCurrentUserLabelPrefersNameThenFallsBackToUsername(): void
    {
        $namedResolver = $this->createMock(TokenStorageUserResolver::class);
        $namedResolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('jdoe')
                ->setName('John Doe')
        );
        $subscriber = new QualityControlSubscriber($namedResolver);

        $method = new \ReflectionMethod($subscriber, 'resolveCurrentUserLabel');
        $method->setAccessible(true);

        self::assertSame('John Doe', $method->invoke($subscriber));

        $fallbackResolver = $this->createMock(TokenStorageUserResolver::class);
        $fallbackResolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('fallback-user')
        );
        $fallbackSubscriber = new QualityControlSubscriber($fallbackResolver);

        self::assertSame('fallback-user', $method->invoke($fallbackSubscriber));
    }
}

final class QualityControlTestObject extends Concrete
{
    private ?string $code = null;
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

final class QualityControlTestLayout
{
    /**
     * @param list<QualityControlTestLayout> $children
     */
    public function __construct(
        private string $name,
        private array $children = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<QualityControlTestLayout>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param list<QualityControlTestLayout> $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }
}
