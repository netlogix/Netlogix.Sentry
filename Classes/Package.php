<?php
declare(strict_types=1);

namespace Netlogix\Sentry;

use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Netlogix\Sentry\ClientOptions\ClientOptionsProviderInterface;

use function Sentry\init;

class Package extends \Neos\Flow\Package\Package
{

    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap) {
            if ($step instanceof Step && $step->getIdentifier() === 'neos.flow:objectmanagement:runtime') {
                $clientOptionsProvider = $bootstrap->getObjectManager()->get(ClientOptionsProviderInterface::class);
                init($clientOptionsProvider->getClientOptions());
            }
        });
    }

}
