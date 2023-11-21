<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Encryption;

use function base64_decode;
use function base64_encode;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class Sealed
{
    /** @var string */
    private $encryptedData;

    /** @var string */
    private $initializationVector;

    /** @var string */
    private $envelopeKey;

    public function __construct(string $encryptedData, string $initializationVector, string $envelopeKeys)
    {
        $this->encryptedData = $encryptedData;
        $this->initializationVector = $initializationVector;
        $this->envelopeKey = $envelopeKeys;
    }

    public static function fromArray(array $package): self
    {
        return new self(
            $package['encryptedData'] ? base64_decode($package['encryptedData'], true) : '',
            $package['initializationVector'] ? base64_decode($package['initializationVector'], true) : '',
            $package['envelopeKey'] ? base64_decode($package['envelopeKey'], true) : ''
        );
    }

    public function toArray(): array
    {
        return [
            'encryptedData' => base64_encode($this->encryptedData),
            'initializationVector' => base64_encode($this->initializationVector),
            'envelopeKey' => base64_encode($this->envelopeKey),
        ];
    }

    public function getEncryptedData(): string
    {
        return $this->encryptedData;
    }

    public function getInitializationVector(): string
    {
        return $this->initializationVector;
    }

    public function getEnvelopeKey(): string
    {
        return $this->envelopeKey;
    }
}
