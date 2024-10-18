<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Context;

interface ContextProvider
{

    /**
     * @return array<string, mixed>
     */
    public function getContexts(): array;

}
