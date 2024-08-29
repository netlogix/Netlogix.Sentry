<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\Scope\ScopeProvider;
use Sentry\CheckInStatus;
use Symfony\Component\Process\Process;
use Throwable;
use function Sentry\captureCheckIn;

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

    /**
     * Run cron with sentry check-in
     *
     * Usage: ./flow sentry:runcron --slug="<slug>" <command with args>
     * Example: ./flow sentry:runcron --slug="foo" cache:collectgarbage "Flow_Mvc_Routing_Route"
     * Example: ./flow sentry:runcron --slug="foo" cache:collectgarbage --cache-identifier="Flow_Mvc_Routing_Route"
     *
     * @param string $slug
     * @return void
     * @throws Throwable
     */
    public function runCronCommand(string $slug): void
    {
        $args = $_SERVER['argv'];

        // unset sentry:runcron
        unset($args[1]);

        $args = array_filter($args, fn (string $arg) => $arg !== '--slug=' . $slug && $arg !== $slug);
        $id = captureCheckIn($slug, CheckInStatus::inProgress());
        $checkIn = fn (CheckInStatus $status) => captureCheckIn($slug, $status, null, null, $id);

        try {
            $process = new Process($args);
            $process->start();
            $lastCheckIn = time();

            foreach ($process as $type => $data) {
                // check in in 1 minute intervals
                // FIXME: this may lead to a monitoring timeout if the child process doesn't output anything for a long time
                if (time() - $lastCheckIn > 60) {
                    $checkIn(CheckInStatus::inProgress());
                    $lastCheckIn = time();
                }

                if ($process::OUT === $type) {
                    fputs(STDOUT, $data);
                } else {
                    fputs(STDERR, $data);
                }
            }

            if ($process->getExitCode() !== 0) {
                $checkIn(CheckInStatus::error());
                return;
            }

            $checkIn(CheckInStatus::ok());
        } catch (Throwable $e) {
            $checkIn(CheckInStatus::error());
            throw $e;
        }
    }

}
