<?php

declare(strict_types=1);

namespace Netlogix\Sentry\ClientOptions;

use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\Integration\NetlogixIntegration;

/**
 * Sets up the basic options for the Sentry client.
 * To add additional options, you can extend this class and register it as
 * implementation for the {@see ClientOptionsProviderInterface}.
 */
class BaseClientOptionsProvider implements ClientOptionsProviderInterface
{
    /**
     * @Flow\InjectConfiguration(package="Netlogix.Sentry", path="dsn")
     * @var string|null
     */
    protected $dsn;

    /**
     * @Flow\InjectConfiguration(package="Netlogix.Sentry", path="inAppExclude")
     * @var string[]|null
     */
    protected $inAppExclude;

    public function getClientOptions(): array
    {
        $dsn = $this->dsn;
        $inAppExclude = $this->inAppExclude;

        return [
            'dsn' => $dsn,
            'integrations' => [
                new NetlogixIntegration($inAppExclude ?? []),
            ],
        ];
    }
}
