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
     * @dataProvider provideLogExceptionExpectations
     */
    public function Event_is_enriched_with_ScopeProvider_data(array $options, bool $isLogged): void
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

}
