<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Netlogix\Sentry\CronMonitoring\CronMonitorFactory;
use Throwable;

/**
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class CronMonitorAspect
{

    /**
     * @Flow\Inject
     * @var CronMonitorFactory
     */
    protected CronMonitorFactory $cronMonitorFactory;

    /**
     * @Flow\Around("method(Neos\Flow\Cli\CommandController->processRequest())")
     * @param JoinPointInterface $joinPoint
     * @return mixed
     * @throws Throwable
     */
    public function onConsoleCommand(JoinPointInterface $joinPoint)
    {
        $arguments = $this->getCommandLineArguments();

        if (!array_key_exists('cron-monitor-slug', $arguments)) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint); // Cron monitor not enabled in application
        }

        $slug = $arguments['cron-monitor-slug'] ?? null;
        $schedule = $arguments['cron-monitor-schedule'] ?? null;
        $maxTime = $arguments['cron-monitor-max-time'] ?? null;
        $checkMargin = $arguments['cron-monitor-check-margin'] ?? null;

        if ($slug === null || $schedule === null) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $cronMonitor = $this->cronMonitorFactory->create(
            (string) $slug,
            (string) $schedule,
            $checkMargin ? (int) $checkMargin : null,
            $maxTime ? (int) $maxTime : null
        );
        $cronMonitor->start();

        try {
            $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
            $cronMonitor->finishSuccess();
            return $result;
        } catch (\Throwable $exception) {
            $cronMonitor->finishError();
            throw $exception;
        }
    }

    private function getCommandLineArguments(): array
    {
        $commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
        $commandLine = array_slice($commandLine, 1);
        if (is_array($commandLine) !== true || count($commandLine) === 0) {
            return [];
        }

        $commandLineArguments = [];
        foreach ($commandLine as $rawCommandLineArgument) {
            if (!str_starts_with($rawCommandLineArgument, '-')) {
                continue; // Skip non-argument parts of the command line
            }
            if (!str_contains($rawCommandLineArgument, '=')) {
                continue; // Skip bool flags or args without explicit equal sign
            }

            list ($name, $value) = explode('=', $rawCommandLineArgument, 2);
            $name = ltrim($name, '-');
            $commandLineArguments[$name] = $value ?? null; // If no value is given, set it to true
        }
        return $commandLineArguments;
    }
}
