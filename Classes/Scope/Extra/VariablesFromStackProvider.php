<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Utility\ObjectAccess;
use Netlogix\Sentry\Scope\ScopeProvider;
use Sentry\SentrySdk;
use Sentry\Serializer\RepresentationSerializer;
use Throwable;
use Traversable;

use function array_combine;
use function array_filter;
use function class_exists;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function method_exists;

/**
 * @Flow\Scope("singleton")
 */
final class VariablesFromStackProvider implements ExtraProvider
{
    private const FUNCTION_PATTERN = '%s::%s()';

    /**
     * @var ScopeProvider
     * @Flow\Inject
     */
    protected $scopeProvider;

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Netlogix.Sentry", path="variableScrubbing.contextDetails")
     */
    protected array $settings = [];

    public function getExtra(): array
    {
        $result = iterator_to_array($this->collectDataFromTraversables(), false);
        if ($result) {
            return ['Method Arguments' => $result];
        } else {
            return [];
        }
    }

    private function collectDataFromTraversables(): Traversable
    {
        $throwable = $this->scopeProvider->getCurrentThrowable();
        while ($throwable instanceof Throwable) {
            yield from $this->collectDataFromTraces($throwable);
            $throwable = $throwable->getPrevious();
        }
    }

    private function collectDataFromTraces(Throwable $throwable): Traversable
    {
        $traces = $throwable->getTrace();
        foreach ($traces as $trace) {
            yield from $this->collectDataFromTrace($trace);
        }
    }

    private function collectDataFromTrace(array $trace): Traversable
    {
        $traceFunction = self::callablePattern($trace['class'] ?? '', $trace['function'] ?? '');

        $settings = iterator_to_array($this->getSettings(), false);
        foreach ($settings as ['className' => $className, 'methodName' => $methodName, 'argumentPaths' => $argumentPaths]) {
            $configFunction = self::callablePattern($className, $methodName);
            if ($traceFunction !== $configFunction) {
                continue;
            }
            $values = [];
            foreach ($argumentPaths as $argumentPathName => $argumentPathLookup) {
                try {
                    $values[$argumentPathName] = $this->representationSerialize(
                        ObjectAccess::getPropertyPath($trace['args'], $argumentPathLookup)
                    );
                } catch (Throwable $t) {
                    $values[$argumentPathName] = '👻';
                }
            }
            yield [$configFunction => $values];
        }
    }

    private function representationSerialize($value)
    {
        static $representationSerialize;

        if (!$representationSerialize) {
            $client = SentrySdk::getCurrentHub()->getClient();
            if ($client) {
                $serializer = new RepresentationSerializer($client->getOptions());
                $representationSerialize = function($value) use ($serializer) {
                    return $serializer->representationSerialize($value);
                };
            } else {
                $representationSerialize = function($value) {
                    return json_encode($value);
                };
            }
        }

        return $representationSerialize($value);
    }

    private function getSettings(): Traversable
    {
        foreach ($this->settings as $config) {
            $className = $config['className'] ?? null;
            if (!$className || !class_exists($className)) {
                continue;
            }

            $methodName = $config['methodName'] ?? null;
            if (!$methodName || !method_exists($className, $methodName)) {
                continue;
            }

            if (!is_array($config['arguments'])) {
                continue;
            }

            $argumentPaths = array_filter($config['arguments'] ?? [], function ($argumentPath) {
                return is_string($argumentPath) && $argumentPath;
            });
            $argumentPaths = array_combine($argumentPaths, $argumentPaths);

            $reflection = new MethodReflection($className, $methodName);
            foreach ($reflection->getParameters() as $parameter) {
                $search = sprintf('/^%s./', $parameter->getName());
                $replace = sprintf('%d.', $parameter->getPosition());
                $argumentPaths = preg_replace($search, $replace, $argumentPaths);
            }

            yield [
                'className' => $className,
                'methodName' => $methodName,
                'argumentPaths' => $argumentPaths
            ];
            yield [
                'className' => $className . '_Original',
                'methodName' => $methodName,
                'argumentPaths' => $argumentPaths
            ];
        }
    }

    private function callablePattern(string $className, string $methodName): string
    {
        return sprintf(self::FUNCTION_PATTERN, $className, $methodName);
    }
}
