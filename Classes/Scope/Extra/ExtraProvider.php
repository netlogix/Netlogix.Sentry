<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

interface ExtraProvider
{

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array;

}
