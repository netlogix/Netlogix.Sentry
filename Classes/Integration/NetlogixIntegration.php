<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Integration;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Utility\Files;
use Neos\Utility\PositionalArraySorter;
use Netlogix\Sentry\Scope\ScopeProvider;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Frame;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\Stacktrace;
use Sentry\State\Scope;
use Sentry\UserDataBag;
use Throwable;

/**
 * @Flow\Proxy(false)
 * @Flow\Autowiring(false)
 */
final class NetlogixIntegration implements IntegrationInterface
{

    /**
     * @var array
     */
    protected static $inAppExclude;

    public function __construct(array $inAppExclude)
    {
        self::$inAppExclude = $inAppExclude;
    }

    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event, EventHint $hint): ?Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);
            if ($integration === null) {
                return $event;
            }

            $event = self::handleEvent($event, $hint);
            if ($event !== null) {
                $event = self::encryptPostBody($event, $hint);
            }

            return $event;
        });
    }

    public static function handleEvent(Event $event, EventHint $hint): ?Event
    {
        if (Bootstrap::$staticObjectManager instanceof CompileTimeObjectManager) {
            return $event;
        }

        if (
            $hint->exception instanceof Throwable &&
            ($configurationManager = Bootstrap::$staticObjectManager->get(ConfigurationManager::class)) !== null
        ) {
            $rules = $configurationManager->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'Netlogix.Sentry.loggingRules.rules'
            ) ?? [];

            $decision = true;

            $positionalArraySorter = new PositionalArraySorter($rules);
            $sortedRules = $positionalArraySorter->toArray();

            foreach (array_keys($sortedRules) as $rule) {
                $decision = Bootstrap::$staticObjectManager
                    ->get($rule)
                    ->decide($hint->exception, $decision);
            }

            if (!$decision) {
                return null;
            }
        }

        $rewrittenExceptions = array_map(
            function ($exception) {
                $stacktrace = $exception->getStacktrace();
                if ($stacktrace !== null) {
                    $exception->setStacktrace(self::rewriteStacktraceAndFlagInApp($stacktrace));
                }
                return $exception;
            },
            $event->getExceptions()
        );

        $event->setExceptions($rewrittenExceptions);

        self::configureScopeForEvent($event, $hint);

        return $event;
    }

    private static function rewriteStacktraceAndFlagInApp(Stacktrace $stacktrace): Stacktrace
    {
        $frames = array_map(function ($frame) {
            $functionName = self::replaceProxyClassName($frame->getFunctionName());
            $classPathAndFilename = self::getOriginalClassPathAndFilename($frame->getFile());
            return new Frame(
                $functionName,
                $classPathAndFilename,
                $frame->getLine(),
                self::replaceProxyClassName($frame->getRawFunctionName()),
                $frame->getAbsoluteFilePath()
                    ? Files::concatenatePaths([FLOW_PATH_ROOT, trim($classPathAndFilename, '/')])
                    : null,
                self::scrubVariablesFromFrame((string)$functionName, $frame->getVars()),
                self::isInApp($classPathAndFilename)
            );
        }, $stacktrace->getFrames());

        return new Stacktrace($frames);
    }

    private static function getOriginalClassPathAndFilename(string $proxyClassPathAndFilename): string
    {
        if (!preg_match('#Flow_Object_Classes/[A-Za-z0-9_]+.php$#', $proxyClassPathAndFilename)) {
            return $proxyClassPathAndFilename;
        }

        $absolutePathAndFilename = Files::concatenatePaths([FLOW_PATH_ROOT, trim($proxyClassPathAndFilename, '/')]);
        if (
            !file_exists($absolutePathAndFilename) ||
            !($proxyClassFile = file_get_contents($absolutePathAndFilename))
        ) {
            return $proxyClassPathAndFilename;
        }

        if (!preg_match('@# PathAndFilename: ([/A-Za-z0-9_.]+\.php)@', $proxyClassFile, $matches)) {
            return $proxyClassPathAndFilename;
        }

        return str_replace(FLOW_PATH_ROOT, '/', str_replace('_', '/', $matches[1]));
    }

    private static function replaceProxyClassName(?string $className): ?string
    {
        return $className ? str_replace('_Original', '', $className) : null;
    }

    private static function isInApp(string $path): bool
    {
        foreach (self::$inAppExclude as $excludePath) {
            if (strpos($path, $excludePath) !== false) {
                return false;
            }
        }
        return true;
    }

    private static function scrubVariablesFromFrame(string $traceFunction, array $frameVariables): array
    {
        if (!$frameVariables) {
            return $frameVariables;
        }
        assert(is_array($frameVariables));

        $config = Bootstrap::$staticObjectManager
            ->get(ConfigurationManager::class)
            ->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Netlogix.Sentry.variableScrubbing'
        ) ?? [];

        $scrubbing = (bool)($config['scrubbing'] ?? false);
        if (!$scrubbing) {
            return $frameVariables;
        }

        $keep = $config['keepFromScrubbing'] ?? [];
        if (!$keep) {
            return [];
        }

        $result = [];
        $traceFunction = str_replace('_Original::', '::', $traceFunction);
        foreach ($keep as $keepConfig) {
            try {
                ['className' => $className, 'methodName' => $methodName, 'arguments' => $arguments] = $keepConfig;
                $configFunction = $className . '::' . $methodName;
                if ($configFunction !== $traceFunction) {
                    continue;
                }
                foreach ($arguments as $argumentName) {
                    $result[$argumentName] = $frameVariables[$argumentName] ?? '👻';
                }

            } catch (\Exception $e) {
            }

        }

        return $result;
    }

    private static function configureScopeForEvent(Event $event, EventHint $hint): void
    {
        try {
            $scopeProvider = Bootstrap::$staticObjectManager->get(ScopeProvider::class);
            if (!$scopeProvider) {
                return;
            }

            $configureEvent = function () use ($event, $scopeProvider) {
                $event->setEnvironment($scopeProvider->collectEnvironment());
                $event->setExtra($scopeProvider->collectExtra());
                foreach ($scopeProvider->collectContexts() as $key => $value) {
                    $event->setContext($key, $value);
                }
                $event->setRelease($scopeProvider->collectRelease());
                $event->setTags($scopeProvider->collectTags());
                $userData = $scopeProvider->collectUser();
                $event->setUser($userData !== [] ? UserDataBag::createFromArray($userData) : null);
            };

            if ($hint->exception instanceof Throwable) {
                $scopeProvider->withThrowable($hint->exception, $configureEvent);
            } else {
                $configureEvent();
            }
        } catch (Throwable $t) {
        }
    }

    /**
     * FIXME: Remove this method, replace by yaml configuration once `EventProcessor` chain is implemented
     *
     * @see https://github.com/netlogix/Netlogix.Sentry/issues/29
     *
     * TODO:
     *  - Crate `EventProcessor` chain as YAML configuration
     *  - Execute `EventProcessor` (instanciate and call) in self::configureScopeForEvent()
     *  - Replace Every ScopeProvider::collect*() method by individual classes
     *  - Configure `EncryptedPayload` as `EventProcessor` in YAML
     *  - Remove this method
     */
    public static function encryptPostBody(Event $event, EventHint $hint): Event
    {
        $encryptedPayload = Bootstrap::$staticObjectManager->get(\Netlogix\Sentry\EventProcessor\EncryptedPayload::class);
        return $encryptedPayload->rewriteEvent($event, $hint);
    }

}
