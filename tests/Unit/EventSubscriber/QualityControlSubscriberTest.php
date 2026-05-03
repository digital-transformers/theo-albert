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

    public function testOnPreSendDataDoesNotInjectLegacyTargetFolderFieldForAuthorizedUsers(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('quality-admin')
                ->setAdmin(true)
        );

        $subscriber = new QualityControlSubscriber($resolver);
        $object = (new QualityControlTestObject())
            ->setClassName('family')
            ->setCode('FAM-001');

        $event = new GenericEvent(null, [
            'object' => $object,
            'data' => [
                'data' => [
                    'name' => 'Visible',
                ],
                'metaData' => [
                    'name' => ['visible'],
                ],
            ],
        ]);

        $subscriber->onPreSendData($event);

        $data = $event->getArgument('data');
        self::assertSame(['name' => 'Visible'], $data['data']);
        self::assertSame(['name' => ['visible']], $data['metaData']);
        self::assertArrayNotHasKey('qualityControlTargetFolder', $data['data']);
    }

    public function testBuildObjectFolderSegmentReturnsNullWithoutCodeOrKeyWhenNotRequired(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(null);

        $subscriber = new QualityControlSubscriber($resolver);
        $object = (new QualityControlTestObject())
            ->setClassName('family')
            ->setCode(null);

        $method = new \ReflectionMethod($subscriber, 'buildObjectFolderSegment');
        $method->setAccessible(true);

        self::assertNull($method->invoke($subscriber, $object, false));
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

    public function testStampRemarksRowsPreservesNumericRowsPostedByTableEditor(): void
    {
        $resolver = $this->createMock(TokenStorageUserResolver::class);
        $resolver->method('getUser')->willReturn(
            (new User())
                ->setUsername('quality-user')
                ->setName('Quality User')
        );

        $subscriber = new QualityControlSubscriber($resolver);
        $object = (new QualityControlTestObject())
            ->setClassName('family')
            ->setQualityControlRemarks([
                ['', '', 'Inspection', 'Open', 'Lens coating issue'],
                ['', '', '', '', ''],
            ]);

        $method = new \ReflectionMethod($subscriber, 'stampRemarksRows');
        $method->setAccessible(true);
        $method->invoke($subscriber, $object);

        $rows = $object->getQualityControlRemarks();
        self::assertCount(1, $rows);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $rows[0]['createdAt']);
        self::assertSame('Quality User', $rows[0]['createdBy']);
        self::assertSame('Inspection', $rows[0]['type']);
        self::assertSame('Open', $rows[0]['status']);
        self::assertSame('Lens coating issue', $rows[0]['remark']);
    }
}

final class QualityControlTestObject extends Concrete
{
    private ?string $code = null;
    private ?self $testParent = null;
    private array $testProperties = [];
    private ?array $qualityControlRemarks = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getQualityControlRemarks(): ?array
    {
        return $this->qualityControlRemarks;
    }

    public function setQualityControlRemarks(?array $qualityControlRemarks): static
    {
        $this->qualityControlRemarks = $qualityControlRemarks;

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

    public function getProperties(): array
    {
        return $this->testProperties;
    }

    public function setProperties(?array $properties): static
    {
        $this->testProperties = $properties ?? [];

        return $this;
    }

    public function getProperty(string $name, bool $asContainer = false): mixed
    {
        if (!array_key_exists($name, $this->testProperties)) {
            return null;
        }

        return $asContainer ? $this->testProperties[$name] : $this->testProperties[$name]->getData();
    }

    public function setProperty(
        string $name,
        string $type,
        mixed $data,
        bool $inherited = false,
        bool $inheritable = false
    ): static {
        $this->testProperties[$name] = new QualityControlTestProperty($data);

        return $this;
    }

    public function hasProperty(string $name): bool
    {
        return array_key_exists($name, $this->testProperties);
    }

    public function removeProperty(string $name): void
    {
        unset($this->testProperties[$name]);
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

final class QualityControlTestProperty
{
    public function __construct(
        private mixed $data,
    ) {
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
