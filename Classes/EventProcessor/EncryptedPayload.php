<?php

declare(strict_types=1);

namespace Netlogix\Sentry\EventProcessor;

use function json_encode;
use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\Encryption\EncryptionService;
use Sentry\Event;
use Sentry\EventHint;

/**
 * @Flow\Scope("singleton")
 */
final class EncryptedPayload implements EventProcessor
{
    private EncryptionService $encryption;

    private bool $encryptPostBody;

    public function injectEncryptionService(EncryptionService $encryption): void
    {
        $this->encryption = $encryption;
    }

    public function injectSettings(array $settings): void
    {
        $privacyCettings = $settings['privacy'] ?? [];
        $this->encryptPostBody = (bool) ($privacyCettings['encryptPostBody'] ?? false);
    }

    public function rewriteEvent(Event $event, EventHint $hint): Event
    {
        if (!$this->encryptPostBody) {
            return $event;
        }

        $request = $event->getRequest();

        $data = $request['data'] ?? [];
        if (!$data || !is_array($data)) {
            return $event;
        }

        $unencrypted = (string) json_encode($data, \JSON_PRETTY_PRINT);

        $encrypted = $this->encryption
            ->seal($unencrypted);
        $uri = (string) $this->encryption
            ->getEncryptionUriForSealedPayload($encrypted);

        $request['data'] = [
            '__ENCRYPTED__DATA__' => $encrypted->toArray(),
        ];
        $event->setRequest($request);

        $extra = $event->getExtra() ?? [];
        $extra['Encrypted POST Data'] = $uri;
        $event->setExtra($extra);

        return $event;
    }
}
