<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\ThrowableStorage;

use Neos\Flow\Tests\FunctionalTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\ThrowableStorage\CompoundStorage;

class CompoundStorageTest extends FunctionalTestCase
{

    /**
     * @test
     */
    public function throwables_are_logged_to_all_storages(): void
    {
        $storage = CompoundStorage::createWithOptions([
            'storages' => [
                TestThrowableStorage1::class,
                TestThrowableStorage2::class,
            ]
        ]);

        $throwable = new Test('foo', 1);
        $additionalData = ['foo' => 'bar'];

        $wasCalled1 = false;
        $wasCalled2 = false;

        TestThrowableStorage1::$logThrowableClosure = static function() use (&$wasCalled1) {
            $wasCalled1 = true;
        };
        TestThrowableStorage2::$logThrowableClosure = static function() use (&$wasCalled2) {
            $wasCalled2 = true;
        };

        $storage->logThrowable($throwable, $additionalData);

        self::assertTrue($wasCalled1);
        self::assertTrue($wasCalled2);
    }

    /**
     * @test
     */
    public function RequestInformationRenderer_and_BacktraceRenderer_are_passed_to_all_storages(): void
    {
        $storage = CompoundStorage::createWithOptions([
            'storages' => [
                TestThrowableStorage1::class,
                TestThrowableStorage2::class,
            ]
        ]);

        $throwable = new Test('foo', 1);

        $requestInformationRenderer = static function() {
        };
        $backtraceRenderer = static function() {
        };
        $storage->setRequestInformationRenderer($requestInformationRenderer);
        $storage->setBacktraceRenderer($backtraceRenderer);

        $storage->logThrowable($throwable);

        self::assertSame($requestInformationRenderer, TestThrowableStorage1::$requestInformationRenderer);
        self::assertSame($requestInformationRenderer, TestThrowableStorage2::$requestInformationRenderer);

        self::assertSame($backtraceRenderer, TestThrowableStorage1::$backtraceRenderer);
        self::assertSame($backtraceRenderer, TestThrowableStorage2::$backtraceRenderer);
    }

}
