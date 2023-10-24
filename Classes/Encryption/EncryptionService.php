<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Encryption;

use function http_build_query;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Security\Cryptography\RsaWalletServiceInterface;
use Neos\Flow\Security\Cryptography\RsaWalletServicePhp;
use Neos\Flow\Security\Exception\InvalidKeyPairIdException;
use Neos\Utility\ObjectAccess;
use function openssl_open;
use function openssl_random_pseudo_bytes;
use function openssl_seal;
use Psr\Http\Message\UriInterface;

class EncryptionService
{
    const ALGORITHM = 'AES256';

    private string $rsaKeyFingerprint;

    private string $encryptionModuleUri;

    private RsaWalletServiceInterface $rsaWallet;

    private BaseUriProvider $baseUriProvider;

    public function injectSettings(array $settings): void
    {
        $privacySettings = $settings['privacy'] ?? [];
        $this->rsaKeyFingerprint = (string) ($privacySettings['rsaKeyFingerprint'] ?? '');
        $this->encryptionModuleUri = (string) ($privacySettings['encryptionModuleUri'] ?? '');
    }

    public function injectRsaWalletService(RsaWalletServiceInterface $rsaWallet): void
    {
        $this->rsaWallet = $rsaWallet;
    }

    public function injectBaseUriProvider(BaseUriProvider $baseUriProvider): void
    {
        $this->baseUriProvider = $baseUriProvider;
    }

    public function seal(string $unencryptedData): Sealed
    {
        $publicKeyString = $this->getKeyString('publicKey');

        $initializationVector = openssl_random_pseudo_bytes(32);

        openssl_seal(
            $unencryptedData,
            $encryptedData,
            $envelopeKeys,
            [$publicKeyString],
            self::ALGORITHM,
            $initializationVector
        );

        return new Sealed($encryptedData, $initializationVector, $envelopeKeys[0]);
    }

    public function open(Sealed $package): string
    {
        $privateKeyString = $this->getKeyString('privateKey');

        $encryptedData = $package->getEncryptedData();
        $envelopeKey = $package->getEnvelopeKey();
        $initializationVector = $package->getInitializationVector();

        openssl_open(
            $encryptedData,
            $unencryptedData,
            $envelopeKey,
            $privateKeyString,
            self::ALGORITHM,
            $initializationVector
        );

        return $unencryptedData;
    }

    public function getEncryptionUriForSealedPayload(Sealed $sealed): UriInterface
    {
        return $this->baseUriProvider
            ->getConfiguredBaseUriOrFallbackToCurrentRequest()
            ->withPath($this->encryptionModuleUri)
            ->withQuery(http_build_query($sealed->toArray()));
    }

    /**
     * @param 'privateKey' | 'publicKey' $slotName
     */
    private function getKeyString(string $slotName): string
    {
        assert($this->rsaWallet instanceof RsaWalletServicePhp);
        // Prime key pair, male rsaWallet load the key pair
        $this->rsaWallet->getPublicKey($this->rsaKeyFingerprint);
        // Private property
        $keys = ObjectAccess::getProperty($this->rsaWallet, 'keys', true);
        // Property path in plain array
        $keyString = ObjectAccess::getPropertyPath(
            $keys,
            sprintf('%s.%s.keyString', $this->rsaKeyFingerprint, $slotName)
        );
        if (!\is_string($keyString)) {
            throw new InvalidKeyPairIdException('Invalid key fingerprint given', 1693231337);
        }

        return $keyString;
    }
}
