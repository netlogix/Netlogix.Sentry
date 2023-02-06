<?php

declare(strict_types=1);

namespace Netlogix\Sentry\LoggingRule;

use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;
use Throwable;

class ExceptionHandlerRenderingGroupsRule implements LoggingRule
{
    /**
     * @Flow\Inject
     * @var ExceptionRenderingOptionsResolver
     */
    protected $optionsResolver;

    public function decide(Throwable $throwable, bool $previousDecision): bool
    {
        $options = $this->optionsResolver->resolveRenderingOptionsForThrowable($throwable);
        return $options['logException'] ?? $previousDecision;
    }
}
