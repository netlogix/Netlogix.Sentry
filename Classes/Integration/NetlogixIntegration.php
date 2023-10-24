<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Integration;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;
use Netlogix\Sentry\Scope\ScopeProvider;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\UserDataBag;
use Throwable;

/**
 * @Flow\Proxy(false)
 * @Flow\Autowiring(false)
 */
final class NetlogixIntegration implements IntegrationInterface
{

    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(static function (Event $event, EventHint $hint): ?Event {
            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);
            if ($integration === null) {
                return $event;
            }

            $event = self::handleEvent($event, $hint);
            $event = self::encryptPostBody($event, $hint);

            return $event;
        });
    }

    public static function handleEvent(Event $event, EventHint $hint): ?Event
    {
        if (Bootstrap::$staticObjectManager instanceof CompileTimeObjectManager) {
            return $event;
        }

        if ($hint->exception instanceof Throwable
            && ($optionsResolver = Bootstrap::$staticObjectManager->get(ExceptionRenderingOptionsResolver::class)) !== null) {
            $options = $optionsResolver->resolveRenderingOptionsForThrowable($hint->exception);
            if (!($options['logException'] ?? true)) {
                return null;
            }
        }

        self::configureScopeForEvent($event, $hint);

        return $event;
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
