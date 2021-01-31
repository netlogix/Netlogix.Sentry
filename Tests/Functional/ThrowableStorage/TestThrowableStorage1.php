<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\ThrowableStorage;

class TestThrowableStorage1 extends TestThrowableStorage
{

    public $options;
    public static $logThrowableClosure;
    public static $requestInformationRenderer;
    public static $backtraceRenderer;

}
