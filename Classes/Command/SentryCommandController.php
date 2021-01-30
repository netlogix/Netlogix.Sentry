<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Command;

use Neos\Flow\Cli\CommandController;
use Neos\Flow\Annotations as Flow;
use Netlogix\Sentry\SentryConfiguration;

/**
 * @Flow\Scope("singleton")
 */
class SentryCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var SentryConfiguration
     */
    protected $sentryConfiguration;

    /**
     * Display the current scope that would be used for new Exceptions
     */
    public function showScopeCommand(): void
    {
        $this->outputLine('Scope Extra:');
        \Neos\Flow\var_dump($this->sentryConfiguration->collectExtra());

        $this->outputLine();

        $this->outputLine('Scope Release:');
        \Neos\Flow\var_dump($this->sentryConfiguration->collectRelease());

        $this->outputLine();

        $this->outputLine('Scope Tags:');
        \Neos\Flow\var_dump($this->sentryConfiguration->collectTags());

        $this->outputLine();

        $this->outputLine('Scope User:');
        \Neos\Flow\var_dump($this->sentryConfiguration->collectUser());
    }

}
