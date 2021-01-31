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
            if (!$hint->exception instanceof Throwable || Bootstrap::$staticObjectManager instanceof CompileTimeObjectManager) {
                return $event;
            }

            $integration = SentrySdk::getCurrentHub()->getIntegration(self::class);
            if ($integration === null) {
                return $event;
            }
            assert($integration instanceof NetlogixIntegration);

            $optionsResolver = Bootstrap::$staticObjectManager->get(ExceptionRenderingOptionsResolver::class);

            $options = $optionsResolver->resolveRenderingOptionsForThrowable($hint->exception);
            if ($options['logException'] ?? false) {
                return null;
            }

            self::configureScopeForEvent($event);

            return $event;
        });
    }

    private static function configureScopeForEvent(Event $event): void
    {
        try {
            $scopeProvider = Bootstrap::$staticObjectManager->get(ScopeProvider::class);

            $event->setExtra($scopeProvider->collectExtra());
            $event->setRelease($scopeProvider->collectRelease());
            $event->setTags($scopeProvider->collectTags());
            $userData = $scopeProvider->collectUser();
            $event->setUser($userData !== [] ? UserDataBag::createFromArray($userData) : null);
        } catch (\Throwable $t) {
        }
    }

}
