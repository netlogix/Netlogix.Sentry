<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\ThrowableStorage;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\ThrowableStorage\CompoundStorage;

class CompoundStorageTest extends UnitTestCase
{

    /**
     * @test
     */
    public function if_no_storages_are_given_an_exception_is_thrown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CompoundStorage::createWithOptions([]);
    }

    /**
     * @test
     */
    public function if_another_CompoundStorage_is_given_as_storage_an_exception_is_thrown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CompoundStorage::createWithOptions([
            'storages' => [
                CompoundStorage::class,
            ]
        ]);
    }

}
