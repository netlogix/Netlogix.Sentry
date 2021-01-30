<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\User;

interface UserProvider
{

    /**
     * @return array<string, mixed>
     */
    public function getUser(): array;

}
