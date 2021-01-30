<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Tags;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;

/**
 * @Flow\Proxy(false)
 */
final class FlowEnvironment implements TagProvider
{

    /**
     * @var Environment
     */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function getTags(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'flow_context' => (string)$this->environment->getContext(),
            'flow_version' => FLOW_VERSION_BRANCH
        ];
    }

}
