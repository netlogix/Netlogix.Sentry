<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Tags;

interface TagProvider
{

    /**
     * @return array<string, string>
     */
    public function getTags(): array;

}
