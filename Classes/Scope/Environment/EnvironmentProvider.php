<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Environment;

interface EnvironmentProvider
{

    /**
     * @return string|null
     */
    public function getEnvironment(): ?string;

}
