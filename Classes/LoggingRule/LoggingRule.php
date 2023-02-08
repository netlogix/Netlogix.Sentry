<?php

declare(strict_types=1);

namespace Netlogix\Sentry\LoggingRule;

use Throwable;

interface LoggingRule
{
    public function decide(Throwable $throwable, bool $previousDecision): bool;
}
