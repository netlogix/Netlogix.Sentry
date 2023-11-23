<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Encryption\EncryptionService;

use Netlogix\Sentry\Encryption\EncryptionService;
use Netlogix\Sentry\Encryption\Sealed;

class SealTest extends EncryptionServiceTestCase
{
    /**
     * @test
     */
    public function EncryptionService_can_seal_payload(): void
    {
        $encryptionService = new EncryptionService();
        $encryptionService->injectSettings([
            'privacy' => [
                'rsaKeyFingerprint' => self::RSA_KEY_FINGERPRINT,
            ],
        ]);

        $encryptionService->injectRsaWalletService(self::getRsaWalletServicePhp());
        $sealed = $encryptionService->seal('foo');

        self::assertInstanceOf(Sealed::class, $sealed);

        $data = $sealed->toArray();

        self::assertArrayHasKey('encryptedData', $data);
        self::assertArrayHasKey('initializationVector', $data);
        self::assertArrayHasKey('envelopeKey', $data);

        self::assertGreaterThan(0, \strlen($data['encryptedData']));
        self::assertGreaterThan(0, \strlen($data['initializationVector']));
        self::assertGreaterThan(0, \strlen($data['envelopeKey']));
    }
}
