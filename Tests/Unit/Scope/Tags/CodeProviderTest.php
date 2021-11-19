<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Extra;

use Neos\Flow\Exception as FlowException;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\ScopeProvider;
use Netlogix\Sentry\Scope\Tags\CodeProvider;
use Throwable;

class CodeProviderTest extends UnitTestCase
{

    /**
     * @test
     */
    public function If_the_currentThrowable_is_not_null_the_code_is_returned(): void
    {
        $throwable = new FlowException('foo', 123);

        $provider = $this->getReferenceCodeProviderWithCurrentThrowable($throwable);

        $tags = $provider->getTags();

        self::assertSame(['code' => 123], $tags);
    }

    /**
     * @test
     */
    public function If_the_currentThrowable_is_null_an_empty_array_is_returned(): void
    {
        $provider = $this->getReferenceCodeProviderWithCurrentThrowable(null);

        $tags = $provider->getTags();

        self::assertSame([], $tags);
    }

    private function getReferenceCodeProviderWithCurrentThrowable(?Throwable $t): CodeProvider
    {
        $scopeProvider = self::getMockBuilder(ScopeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeProvider
            ->expects(self::once())
            ->method('getCurrentThrowable')
            ->willReturn($t);

        assert($scopeProvider instanceof ScopeProvider);

        return new CodeProvider($scopeProvider);
    }

}