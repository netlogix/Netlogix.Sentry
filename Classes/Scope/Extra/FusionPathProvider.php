<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Exception\RuntimeException as FusionRuntimeException;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
final class FusionPathProvider implements ExtraProvider
{
    private array $fusionPaths = [];

    /**
     * @Flow\Before("setting(Netlogix.Sentry.featureFlags.fusionFeatures) && within(Neos\Fusion\Core\ExceptionHandlers\AbstractRenderingExceptionHandler) && method(.*->handleRenderingException())")
     */
    public function beforeFusionExceptionHandling(JoinPointInterface $joinPoint): void
    {
        if (!$joinPoint->isMethodArgument('fusionPath')) {
            return;
        }

        $fusionPath = $joinPoint->getMethodArgument('fusionPath');
        if ($joinPoint->isMethodArgument('exception')) {
            $exception = $joinPoint->getMethodArgument('exception');
            if ($exception instanceof FusionRuntimeException) {
                $fusionPath = $exception->getFusionPath();
            }
        }

        $this->fusionPaths[] = $fusionPath;
    }

    public function getExtra(): array
    {
        if (empty($this->fusionPaths)) {
            return [];
        }

        return [
            'fusionPaths' => array_values(array_unique($this->fusionPaths)),
        ];
    }
}
