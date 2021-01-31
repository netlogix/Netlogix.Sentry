<?php
declare(strict_types=1);

namespace Netlogix\Sentry\ExceptionHandler;

use Neos\Flow\Error\AbstractExceptionHandler;
use Neos\Flow\Annotations as Flow;
use Throwable;

/**
 * @Flow\Scope("singleton")
 * @internal Only used to resolve Exception rendering options. DO NOT USE!
 */
final class ExceptionRenderingOptionsResolver extends AbstractExceptionHandler
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }

    public function resolveRenderingOptionsForThrowable(Throwable $throwable): array
    {
        return $this->resolveCustomRenderingOptions($throwable);
    }

    /**
     * @param Throwable $exception
     * @internal
     */
    public function handleException($exception)
    {
        self::throwWhenUsed();
    }

    protected function echoExceptionWeb($exception)
    {
        self::throwWhenUsed();
    }

    protected function echoExceptionCli(\Throwable $exception)
    {
        self::throwWhenUsed();
    }

    private static function throwWhenUsed(): void
    {
        throw new \RuntimeException('This Exception Handler should not be used!', 1612044864);
    }

}
