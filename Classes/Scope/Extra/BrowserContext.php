<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Extra;

use Jenssegers\Agent\Agent;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class BrowserContext implements ExtraProvider
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
        $browser = $this->agent->browser();

        return [
            'contexts' => [
                'browser' => [
                    'name' => $browser,
                    'version' => $this->agent->version($browser)
                ]
            ]
        ];
    }

}
