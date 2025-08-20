<?php

declare(strict_types=1);

namespace Netlogix\Sentry\ClientOptions;

/**
 * Interface to provide options to the Sentry client.
 * You may want to extend the {@see BaseClientOptionsProvider} instead.
 */
interface ClientOptionsProviderInterface
{
    /**
     * Provide the options used to initialize the Sentry client.
     * @return array<string, mixed>
     */
    public function getClientOptions(): array;
}
