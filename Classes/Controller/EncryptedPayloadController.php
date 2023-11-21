<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Controller;

use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use Neos\Flow\Mvc\Controller\ActionController;
use Netlogix\Sentry\Encryption\EncryptionService;
use Netlogix\Sentry\Encryption\Sealed;

class EncryptedPayloadController extends ActionController
{
    protected EncryptionService $encryptionService;

    public function injectEncryptionService(EncryptionService $encryptionService): void
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param string $encryptedData
     * @param string $initializationVector
     * @param string $envelopeKey
     */
    public function decryptAction(
        string $encryptedData,
        string $initializationVector,
        string $envelopeKey
    ): string {
        $sealed = Sealed::fromArray([
            'encryptedData' => $encryptedData,
            'initializationVector' => $initializationVector,
            'envelopeKey' => $envelopeKey,
        ]);
        $unencrypted = $this->encryptionService->open($sealed);
        $this->response->setContentType('application/json');

        return json_encode(json_decode($unencrypted, true), JSON_PRETTY_PRINT);
    }
}
