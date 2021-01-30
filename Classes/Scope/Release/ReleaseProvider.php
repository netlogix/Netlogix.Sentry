<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Release;

interface ReleaseProvider
{

    /**
     * @return string|null
     */
    public function getRelease(): ?string;

}
