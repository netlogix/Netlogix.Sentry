<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Tags;

use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\Scope\ScopeProvider;

/**
 * @Flow\Scope("singleton")
 */
final class CodeProvider implements TagProvider
{

    private ScopeProvider $scopeProvider;

    public function __construct(ScopeProvider $scopeProvider)
    {
        $this->scopeProvider = $scopeProvider;
    }

    public function getTags(): array
    {
        $throwable = $this->scopeProvider->getCurrentThrowable();

        if ($throwable === null) {
            return [];
        }

        return ['code' => $throwable->getCode()];
    }

}