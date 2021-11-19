<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception as FlowException;
use Netlogix\Sentry\Scope\ScopeProvider;

/**
 * @Flow\Scope("singleton")
 */
final class ReferenceCodeProvider implements ExtraProvider
{

    /**
     * @var ScopeProvider
     */
    private $scopeProvider;

    public function __construct(ScopeProvider $scopeProvider)
    {
        $this->scopeProvider = $scopeProvider;
    }

    public function getExtra(): array
    {
        $throwable = $this->scopeProvider->getCurrentThrowable();

        if (!$throwable instanceof FlowException) {
            return [];
        }

        return ['referenceCode' => $throwable->getReferenceCode()];
    }

}