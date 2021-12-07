<?php
declare(strict_types=1);

namespace Netlogix\Sentry\ExceptionHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\AbstractExceptionHandler;
use RuntimeException;
use Throwable;

/**
 * @Flow\Scope("singleton")
 * @internal Only used to resolve Exception rendering options. DO NOT USE!
 */
final class ExceptionRenderingOptionsResolver extends AbstractExceptionHandler
{

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Neos.Flow", path="error.exceptionHandler")
     */
    protected $options;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }

    public function resolveRenderingOptionsForThrowable(Throwable $throwable): array
    {
        return $this->resolveCustomRenderingOptions($throwable);
    }

    protected function resolveRenderingGroup(\Throwable $exception)
    {
        if (!isset($this->options['renderingGroups'])) {
            return null;
        }
        $renderingGroup = parent::resolveRenderingGroup($exception);
        if ($renderingGroup === null) {
            // try to match using exception code
            foreach ($this->options['renderingGroups'] as $renderingGroupName => $renderingGroupSettings) {
                if (isset($renderingGroupSettings['matchingExceptionCodes'])) {
                    foreach ($renderingGroupSettings['matchingExceptionCodes'] as $exceptionCode) {
                        if ($exception->getCode() === $exceptionCode) {
                            return $renderingGroupName;
                        }
                    }
                }
            }
        }

        return $renderingGroup;
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

    protected function echoExceptionCli(Throwable $exception)
    {
        self::throwWhenUsed();
    }

    private static function throwWhenUsed(): void
    {
        throw new RuntimeException('This Exception Handler should not be used!', 1612044864);
    }

}
