<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Environment;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class FlowSettings implements EnvironmentProvider
{

    /**
     * @var string|null
     */
    protected $environment;

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function injectSettings(array $settings): void
    {
        $environment = $settings['environment'] ?? [];
        $this->environment = (string)($environment['setting'] ?? '');
        if ($this->environment === '') {
            $this->environment = null;
        }
    }

}
