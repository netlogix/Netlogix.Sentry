<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Encryption\EncryptionService;

use Neos\Flow\Security\Cryptography\RsaWalletServicePhp;
use Neos\Flow\Tests\UnitTestCase;

abstract class EncryptionServiceTestCase extends UnitTestCase
{
    protected const RSA_KEY_FINGERPRINT = '37f0cb0d76d54a9d2d0187a0ec2846e7';

    protected const RSA_WALLET_FILE = __DIR__ . '/RsaWalletData.bin';

    protected static function getRsaWalletServicePhp(): RsaWalletServicePhp
    {
        $wallet = new RsaWalletServicePhp();
        $wallet->injectSettings([
            'security' => [
                'cryptography' => [
                    'RSAWalletServicePHP' => [
                        'keystorePath' => self::RSA_WALLET_FILE,
                        'paddingAlgorithm' => 0,
                    ],
                ],
            ],
        ]);
        $wallet->initializeObject();

        return $wallet;
    }
}
