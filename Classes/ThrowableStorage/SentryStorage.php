<?php
declare(strict_types=1);

namespace Netlogix\Sentry\ThrowableStorage;

use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Annotations as Flow;
use Sentry\State\Scope;
use Throwable;
use function Sentry\captureException;
use function Sentry\withScope;

/**
 * @Flow\Proxy(false)
 * @Flow\Autowiring(false)
 */
final class SentryStorage implements ThrowableStorageInterface
{

    public static function createWithOptions(array $options): ThrowableStorageInterface
    {
        return new SentryStorage();
    }

    public function logThrowable(Throwable $throwable, array $additionalData = [])
    {
        withScope(function(Scope $scope) use (&$eventId, $throwable, $additionalData) {
            $scope->setExtras($additionalData);
            $eventId = captureException($throwable);
        });

        if ($eventId) {
            return 'See sentry: ' . (string)$eventId;
        }

        return '';
    }

    public function setRequestInformationRenderer(\Closure $requestInformationRenderer)
    {
    }

    public function setBacktraceRenderer(\Closure $backtraceRenderer)
    {
    }

}
