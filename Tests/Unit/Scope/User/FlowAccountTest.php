<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\User;

use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\User\FlowAccount;

class FlowAccountTest extends UnitTestCase
{

    /**
     * @test
     */
    public function if_security_context_is_not_initialized_no_account_is_fetched(): void
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context
            ->expects(self::once())
            ->method('isInitialized')
            ->willReturn(false);

        $context
            ->expects(self::never())
            ->method('getAccount');

        $flowAccount = new FlowAccount($context);

        self::assertSame([], $flowAccount->getUser());
    }

    /**
     * @test
     */
    public function if_no_account_is_authenticated_an_empty_array_is_returned(): void
    {
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context
            ->expects(self::once())
            ->method('isInitialized')
            ->willReturn(true);

        $context
            ->expects(self::once())
            ->method('getAccount')
            ->willReturn(null);

        $flowAccount = new FlowAccount($context);

        self::assertSame([], $flowAccount->getUser());
    }

    /**
     * @test
     */
    public function accountIdentifier_is_extracted_from_security_context(): void
    {
        $account = $this->getMockBuilder(Account::class)
            ->getMock();

        $account
            ->expects(self::once())
            ->method('getAccountIdentifier')
            ->willReturn('lars');

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context
            ->expects(self::once())
            ->method('isInitialized')
            ->willReturn(true);

        $context
            ->expects(self::once())
            ->method('getAccount')
            ->willReturn($account);

        $flowAccount = new FlowAccount($context);

        self::assertSame(['username' => 'lars'], $flowAccount->getUser());
    }

}
