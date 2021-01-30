<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\User;

use Neos\Flow\Security\Context;

final class FlowAccount implements UserProvider
{

    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getUser(): array
    {
        if (!$this->context->isInitialized()
            || ($account = $this->context->getAccount()) === null) {
            return [];
        }

        return [
            'username' => $account->getAccountIdentifier()
        ];
    }

}
