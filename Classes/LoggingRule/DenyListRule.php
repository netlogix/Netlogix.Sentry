<?php
declare(strict_types=1);

namespace Netlogix\Sentry\LoggingRule;

use Neos\Flow\Annotations as Flow;
use Throwable;

class DenyListRule implements LoggingRule
{
    /**
     * @Flow\InjectConfiguration(path="loggingRules.denyList")
     * @var array
     */
    protected $denyList = [];

    public function decide(Throwable $throwable, bool $previousDecision): bool
    {
        foreach ($this->denyList as $deniedThrowableClassName) {
            if ($throwable instanceof $deniedThrowableClassName) {
                return false;
            }
        }

        return $previousDecision;
    }
}
