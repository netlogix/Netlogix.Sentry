<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\Scope\ScopeProvider;

/**
 * @Flow\Scope("singleton")
 */
class SentryCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var ScopeProvider
     */
    protected $scopeProvider;

    /**
     * Throw a test exception that should be logged to sentry
     *
     * @throws Test
     */
    public function testCommand(): void
    {
        throw new Test('This Exception should be logged to sentry.io!', 1612045236);
    }

    /**
     * Display the current scope that would be used for new Exceptions
     */
    public function showScopeCommand(): void
    {
        $this->outputLine('Scope Environment:');
        \Neos\Flow\var_dump($this->scopeProvider->collectEnvironment());

        $this->outputLine();

        $this->outputLine('Scope Extra:');
        \Neos\Flow\var_dump($this->scopeProvider->collectExtra());

        $this->outputLine();

        $this->outputLine('Scope Release:');
        \Neos\Flow\var_dump($this->scopeProvider->collectRelease());

        $this->outputLine();

        $this->outputLine('Scope Tags:');
        \Neos\Flow\var_dump($this->scopeProvider->collectTags());

        $this->outputLine();

        $this->outputLine('Scope User:');
        \Neos\Flow\var_dump($this->scopeProvider->collectUser());
    }

}
