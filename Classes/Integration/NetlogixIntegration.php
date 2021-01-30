<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Integration;

use Neos\Flow\Annotations as Flow;
use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * @Flow\Proxy(false)
 */
final class NetlogixIntegration implements IntegrationInterface
{

    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event): ?Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);

            return $event;
        });
    }

}
