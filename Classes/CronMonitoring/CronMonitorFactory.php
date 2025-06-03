<?php

declare(strict_types=1);

namespace Netlogix\Sentry\CronMonitoring;

use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\SentrySdk;

class CronMonitorFactory
{
    public function create(string $slug, string $schedule, ?int $checkMarginMinutes = null, ?int $maxRuntimeMinutes = null): CronMonitor
    {
        $monitorSchedule = MonitorSchedule::crontab($schedule);
        $monitorConfig = new MonitorConfig(
            $monitorSchedule,
            $checkMarginMinutes,
            $maxRuntimeMinutes,
            date_default_timezone_get()
        );

        return new CronMonitor(SentrySdk::getCurrentHub(), $monitorConfig, $slug);
    }
}
