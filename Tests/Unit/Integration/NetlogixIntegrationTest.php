<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Integration;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;
use Netlogix\Sentry\Integration\NetlogixIntegration;
use Netlogix\Sentry\LoggingRule\ExceptionHandlerRenderingGroupsRule;
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
     * @dataProvider provideExceptionLoggingExpectations
     */
    public function Event_is_logged_depending_on_loggingRules(bool $ruleResult, bool $isLogged): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configurationManager
            ->method('getConfiguration')
            ->with(  ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Netlogix.Sentry.loggingRules.rules')
            ->willReturn([
                'Netlogix\Sentry\LoggingRule\ExceptionHandlerRenderingGroupsRule' => '10'
            ]);

        $exceptionHandlerRenderingGroupsRule = $this->getMockBuilder(ExceptionHandlerRenderingGroupsRule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exceptionHandlerRenderingGroupsRule
            ->method('decide')
            ->willReturn($ruleResult);

        $objectManager
            ->method('get')
            ->with($this->logicalOr(
                $this->equalTo( ConfigurationManager::class),
                $this->equalTo(ExceptionHandlerRenderingGroupsRule::class)
            ))
            ->will($this->returnCallback(function($class) use ($configurationManager, $exceptionHandlerRenderingGroupsRule) {
                if ($class === ConfigurationManager::class) {
                    return $configurationManager;
                } else if ($class === ExceptionHandlerRenderingGroupsRule::class) {
                    return $exceptionHandlerRenderingGroupsRule;
                }

                return null;
            }));


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

    /**
     * @test
     */
    public function Event_is_logged_when_no_rules_are_defined(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configurationManager
            ->method('getConfiguration')
            ->with(  ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Netlogix.Sentry.loggingRules.rules')
            ->willReturn([]);

        $objectManager
            ->method('get')
            ->with(ConfigurationManager::class)
            ->willReturn($configurationManager);

        Bootstrap::$staticObjectManager = $objectManager;

        $throwable = new Test('foo', 1612089648);

        $event = Event::createEvent();
        $hint = EventHint::fromArray(['exception' => $throwable]);

        self::assertSame($event, NetlogixIntegration::handleEvent($event, $hint));
    }

    public function provideExceptionLoggingExpectations(): iterable
    {
        yield 'If ruleResult is false, null is returned' => [
            'ruleResult' => false,
            'isLogged' => false,
        ];

        yield 'If ruleResult is true, the exception is logged' => [
            'ruleResult' => true,
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
            ->setMethods(
                [
                    'collectEnvironment',
                    'collectExtra',
                    'collectRelease',
                    'collectTags',
                    'collectUser',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $scopeProvider
            ->expects(self::once())
            ->method('collectEnvironment')
            ->willReturn('my-machine');

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

        self::assertSame('my-machine', $event->getEnvironment());

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

    /**
     * @test
     */
    public function ScopeProvider_receives_the_current_Exception(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $scopeProvider = $this->getMockBuilder(ScopeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $throwable = new Test('foo', 1612089648);

        $scopeProvider
            ->expects(self::once())
            ->method('withThrowable')
            ->with($throwable);

        $objectManager
            ->method('get')
            ->withConsecutive([ExceptionRenderingOptionsResolver::class], [ScopeProvider::class])
            ->willReturnOnConsecutiveCalls(new ExceptionRenderingOptionsResolver(), $scopeProvider);

        Bootstrap::$staticObjectManager = $objectManager;

        $event = Event::createEvent();
        $hint = EventHint::fromArray(['exception' => $throwable]);

        NetlogixIntegration::handleEvent($event, $hint);
    }

}
