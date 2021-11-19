<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Extra;

use Exception;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\Extra\ReferenceCodeProvider;
use Netlogix\Sentry\Scope\ScopeProvider;
use Throwable;

class ReferenceCodeProviderTest extends UnitTestCase
{

    /**
     * @test
     */
    public function If_the_currentThrowable_is_a_Flow_Exception_the_referenceCode_is_returned(): void
    {
        $throwable = new FlowException('foo', 123);

        $provider = $this->getReferenceCodeProviderWithCurrentThrowable($throwable);

        $extra = $provider->getExtra();

        self::assertArrayHasKey('referenceCode', $extra);
        self::assertNotEmpty($extra['referenceCode']);
    }

    /**
     * @test
     */
    public function If_the_currentThrowable_is_not_a_Flow_Exception_an_empty_array_is_returned(): void
    {
        $throwable = new Exception('foo', 123);

        $provider = $this->getReferenceCodeProviderWithCurrentThrowable($throwable);

        $extra = $provider->getExtra();

        self::assertSame([], $extra);
    }

    /**
     * @test
     */
    public function If_currentThrowable_is_null_an_empty_array_is_returned(): void
    {
        $throwable = new Exception('foo', 123);

        $provider = $this->getReferenceCodeProviderWithCurrentThrowable($throwable);

        $extra = $provider->getExtra();

        self::assertSame([], $extra);
    }

    private function getReferenceCodeProviderWithCurrentThrowable(Throwable $t): ReferenceCodeProvider
    {
        $scopeProvider = self::getMockBuilder(ScopeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeProvider
            ->expects(self::once())
            ->method('getCurrentThrowable')
            ->willReturn($t);

        assert($scopeProvider instanceof ScopeProvider);

        return new ReferenceCodeProvider($scopeProvider);
    }

}