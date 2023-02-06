<?php
declare(strict_types=1);

namespace Netlogix\Sentry\LoggingRule;

use Neos\Flow\Annotations as Flow;
use Throwable;

class AllowListRule implements LoggingRule
{
    /**
     * @Flow\InjectConfiguration(path="loggingRules.allowList")
     * @var array
     */
    protected array $allowList = [];

    public function decide(Throwable $throwable, bool $previousDecision): bool
    {
        foreach ($this->allowList as $allowedThrowableClassName) {
            if ($throwable instanceof $allowedThrowableClassName) {
                return true;
            }
        }

        return $previousDecision;
    }
}
