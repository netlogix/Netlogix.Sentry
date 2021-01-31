<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Functional\ThrowableStorage;

class TestThrowableStorage2 extends TestThrowableStorage
{

    public $options;
    public static $logThrowableClosure;
    public static $requestInformationRenderer;
    public static $backtraceRenderer;

}
