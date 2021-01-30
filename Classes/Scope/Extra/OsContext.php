<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

use Jenssegers\Agent\Agent;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class OsContext implements ExtraProvider
{

    /**
     * @var Agent
     */
    private $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    public function getExtra(): array
    {
        $platform = $this->agent->platform();

        return [
            'contexts' => [
                'os' => [
                    'name' => $platform,
                    'version' => $this->agent->version($platform)
                ]
            ]
        ];
    }

}
