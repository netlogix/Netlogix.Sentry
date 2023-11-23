<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\RewriteEvent;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Encryption\EncryptionService;
use Netlogix\Sentry\Encryption\Sealed;
use Netlogix\Sentry\EventProcessor\EncryptedPayload;
use Netlogix\Sentry\Integration\NetlogixIntegration;
use PHP_CodeSniffer\Tokenizers\JS;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\EventId;

class RewriteEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function No_configuration_does_not_replace_the_request(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $encryptedPayload = new EncryptedPayload();
        $encryptedPayload->injectSettings([]);

        $objectManager
            ->expects(self::once())
            ->method('get')
            ->with(EncryptedPayload::class)
            ->willReturn($encryptedPayload);

        Bootstrap::$staticObjectManager = $objectManager;

        $event = Event::createEvent(EventId::generate());
        $request = [
            'data' => [
                'foo' => 'bar',
            ],
        ];
        $event->setRequest($request);

        $newEvent = NetlogixIntegration::encryptPostBody($event, new EventHint());
        $newRequest = $newEvent->getRequest();

        self::assertSame($request, $newRequest);
    }

    /**
     * @test
     */
    public function Enabled_encryption_but_no_request_data_does_not_replace_the_request(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $encryptedPayload = new EncryptedPayload();
        $encryptedPayload->injectSettings([
            'privacy' => [
                'encryptPostBody' => true,
            ],
        ]);

        $objectManager
            ->expects(self::once())
            ->method('get')
            ->with(EncryptedPayload::class)
            ->willReturn($encryptedPayload);

        Bootstrap::$staticObjectManager = $objectManager;

        $request = [
            'whatever' => [
                'foo' => 'bar',
            ],
        ];
        $event = Event::createEvent(EventId::generate());
        $event->setRequest($request);

        $newEvent = NetlogixIntegration::encryptPostBody($event, new EventHint());
        $newRequest = $newEvent->getRequest();

        self::assertEquals($request, $newRequest);
    }

    /**
     * @test
     */
    public function Enabled_encryption_calls_the_encryption_service_and_rewrites_the_request_body(): void
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $request = [
            'data' => [
                'foo' => 'bar',
            ],
        ];

        $encryption = $this->createMock(EncryptionService::class);
        $encryption
            ->expects(self::once())
            ->method('seal')
            ->with(\json_encode($request['data'], \JSON_PRETTY_PRINT))
            ->willReturn(new Sealed('$encryptedData', '$initializationVector', '$envelopeKeys'));

        $encryptedPayload = new EncryptedPayload();
        $encryptedPayload->injectSettings([
            'privacy' => [
                'encryptPostBody' => true,
            ],
        ]);
        $encryptedPayload->injectEncryptionService($encryption);

        $objectManager
            ->expects(self::once())
            ->method('get')
            ->with(EncryptedPayload::class)
            ->willReturn($encryptedPayload);

        Bootstrap::$staticObjectManager = $objectManager;

        $event = Event::createEvent(EventId::generate());
        $event->setRequest($request);

        $newEvent = NetlogixIntegration::encryptPostBody($event, new EventHint());
        $request = $newEvent->getRequest();

        self::assertEquals(
            [
                'data' => [
                    '__ENCRYPTED__DATA__' => [
                        'encryptedData' => \base64_encode('$encryptedData'),
                        'initializationVector' => \base64_encode('$initializationVector'),
                        'envelopeKey' => \base64_encode('$envelopeKeys'),
                    ],
                ],
            ],
            $request
        );
    }
}
