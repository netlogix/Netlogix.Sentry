<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\ThrowableStorage;

use Closure;
use Neos\Flow\Log\ThrowableStorageInterface;
use Throwable;

abstract class TestThrowableStorage implements ThrowableStorageInterface
{

    protected function __construct(array $options)
    {
        $this->options = $options;
    }

    public static function createWithOptions(array $options): ThrowableStorageInterface
    {
        return new static($options);
    }

    public function logThrowable(Throwable $throwable, array $additionalData = [])
    {
        if (is_callable(static::$logThrowableClosure)) {
            (static::$logThrowableClosure)($throwable, $additionalData);
        }
    }

    public function setRequestInformationRenderer(Closure $requestInformationRenderer)
    {
        static::$requestInformationRenderer = $requestInformationRenderer;
    }

    public function setBacktraceRenderer(Closure $backtraceRenderer)
    {
        static::$backtraceRenderer = $backtraceRenderer;
    }

}
