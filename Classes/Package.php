<?php
declare(strict_types=1);

namespace Netlogix\Sentry;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Netlogix\Sentry\Integration\NetlogixIntegration;
use function Sentry\init;

class Package extends \Neos\Flow\Package\Package
{

    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(
            ConfigurationManager::class,
            'configurationManagerReady',
            static function (ConfigurationManager $configurationManager) {
                $dsn = $configurationManager->getConfiguration(
                    ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    'Netlogix.Sentry.dsn'
                );

                $inAppExclude = $configurationManager->getConfiguration(
                    ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    'Netlogix.Sentry.inAppExclude'
                );
                
                init([
                    'dsn' => $dsn,
                    'integrations' => [
                        new NetlogixIntegration($inAppExclude ?? []),
                    ]
                ]);
            }
        );
    }

}
