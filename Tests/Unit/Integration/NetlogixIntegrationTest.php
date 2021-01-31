<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\Integration;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;
use Netlogix\Sentry\Integration\NetlogixIntegration;
use Netlogix\Sentry\Scope\ScopeProvider;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;

class NetlogixIntegrationTest extends UnitTestCase
{

    /**
     * @test
     */
    public function If_event_hint_does_not_contain_an_exception_it_is_not_filtered(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $optionsResolver = new ExceptionRenderingOptionsResolver();
        $optionsResolver->setOptions([
            'renderingGroups' => [
                'netlogixSentryTest' => [
                    'matchingExceptionClassNames' => [Test::class],
                    'options' => [
                        'logException' => false
                    ]
                ]
            ]
        ]);

        $objectManager
            ->method('get')
            ->with(ExceptionRenderingOptionsResolver::class)
            ->willReturn($optionsResolver);

        Bootstrap::$staticObjectManager = $objectManager;

        $event = Event::createEvent();
        $hint = EventHint::fromArray(['exception' => null]);

        self::assertSame($event, NetlogixIntegration::handleEvent($event, $hint));
    }

    /**
     * @test
     * @dataProvider provideLogExceptionExpectations
     */
    public function Event_is_logged_depending_on_logException(array $options, bool $isLogged): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $optionsResolver = new ExceptionRenderingOptionsResolver();
        $optionsResolver->setOptions([
            'renderingGroups' => [
                'netlogixSentryTest' => [
                    'matchingExceptionClassNames' => [Test::class],
                    'options' => $options
                ]
            ]
        ]);

        $objectManager
            ->method('get')
            ->with(ExceptionRenderingOptionsResolver::class)
            ->willReturn($optionsResolver);

        Bootstrap::$staticObjectManager = $objectManager;

        $throwable = new Test('foo', 1612089648);

        $event = Event::createEvent();
        $hint = EventHint::fromArray(['exception' => $throwable]);

        if ($isLogged) {
            self::assertSame($event, NetlogixIntegration::handleEvent($event, $hint));
        } else {
            self::assertNull(NetlogixIntegration::handleEvent($event, $hint));
        }
    }

    public function provideLogExceptionExpectations(): iterable
    {
        yield 'If logException is false, null is returned' => [
            'options' => [
                'logException' => false,
            ],
            'isLogged' => false,
        ];

        yield 'If logException is true, the exception is logged' => [
            'options' => [
                'logException' => true,
            ],
            'isLogged' => true,
        ];

        yield 'If logException is unset, the exception is logged' => [
            'options' => [],
            'isLogged' => true,
        ];
    }

    /**
     * @test
     */
    public function Event_is_enriched_with_ScopeProvider_data(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $scopeProvider = $this->getMockBuilder(ScopeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeProvider
            ->expects(self::once())
            ->method('collectExtra')
            ->willReturn(['foo' => 'bar']);

        $scopeProvider
            ->expects(self::once())
            ->method('collectRelease')
            ->willReturn('release-123');

        $scopeProvider
            ->expects(self::once())
            ->method('collectTags')
            ->willReturn(['bar' => 'baz']);

        $scopeProvider
            ->expects(self::once())
            ->method('collectUser')
            ->willReturn(['username' => 'lars']);

        $objectManager
            ->method('get')
            ->withConsecutive([ExceptionRenderingOptionsResolver::class], [ScopeProvider::class])
            ->willReturnOnConsecutiveCalls(new ExceptionRenderingOptionsResolver(), $scopeProvider);

        Bootstrap::$staticObjectManager = $objectManager;

        $throwable = new Test('foo', 1612089648);

        $event = Event::createEvent();
        $hint = EventHint::fromArray(['exception' => $throwable]);

        $enrichedEvent = NetlogixIntegration::handleEvent($event, $hint);

        self::assertSame($event, $enrichedEvent);

        $extra = $event->getExtra();
        self::assertArrayHasKey('foo', $extra);
        self::assertSame('bar', $extra['foo']);

        self::assertSame('release-123', $event->getRelease());

        $tags = $event->getTags();
        self::assertArrayHasKey('bar', $tags);
        self::assertSame('baz', $tags['bar']);

        self::assertInstanceOf(UserDataBag::class, $event->getUser());
        self::assertSame('lars', $event->getUser()->getUsername());
    }

    /**
     * @test
     */
    public function Events_are_also_enriched_if_hint_does_not_contain_a_throwable(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $scopeProvider = $this->getMockBuilder(ScopeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeProvider
            ->method('collectExtra')
            ->willReturn([]);

        $scopeProvider
            ->method('collectRelease')
            ->willReturn('release-123');

        $scopeProvider
            ->method('collectTags')
            ->willReturn([]);

        $scopeProvider
            ->method('collectUser')
            ->willReturn([]);

        $objectManager
            ->method('get')
            ->with(ScopeProvider::class)
            ->willReturn($scopeProvider);

        Bootstrap::$staticObjectManager = $objectManager;

        $event = Event::createEvent();
        $hint = EventHint::fromArray([]);

        $enrichedEvent = NetlogixIntegration::handleEvent($event, $hint);

        self::assertSame($event, $enrichedEvent);
        self::assertSame('release-123', $event->getRelease());
    }

}
