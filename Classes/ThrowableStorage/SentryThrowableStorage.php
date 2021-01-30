<?php
declare(strict_types=1);

namespace Netlogix\Sentry\ThrowableStorage;

use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Annotations as Flow;
use function Sentry\captureException;

/**
 * @Flow\Proxy(false)
 * @Flow\Autowiring(false)
 */
class SentryThrowableStorage implements ThrowableStorageInterface
{

    public static function createWithOptions(array $options): ThrowableStorageInterface
    {
        return new static();
    }

    public function logThrowable(\Throwable $throwable, array $additionalData = [])
    {
        captureException($throwable);

        $errorCodeNumber = ($throwable->getCode() > 0) ? ' #' . $throwable->getCode() : '';
        $backTrace = $throwable->getTrace();
        $line = isset($backTrace[0]['line']) ? ' in line ' . $backTrace[0]['line'] . ' of ' . $backTrace[0]['file'] : '';

        return 'Exception' . $errorCodeNumber . $line . ': ' . $throwable->getMessage();
    }

    public function setRequestInformationRenderer(\Closure $requestInformationRenderer)
    {
    }

    public function setBacktraceRenderer(\Closure $backtraceRenderer)
    {
    }

}
