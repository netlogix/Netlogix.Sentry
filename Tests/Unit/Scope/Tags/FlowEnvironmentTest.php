<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Tags;

use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Environment;
use Netlogix\Sentry\Scope\Tags\FlowEnvironment;

class FlowEnvironmentTest extends UnitTestCase
{

    /**
     * @var Environment
     */
    private $environment;

    protected function setUp(): void
    {
        $context = new ApplicationContext('Production/NetlogixSentry');

        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($context);
    }

    /**
     * @test
     */
    public function flow_context_is_extracted_from_environment(): void
    {
        $flowEnvironment = new FlowEnvironment($this->environment);

        $tags = $flowEnvironment->getTags();
        self::assertArrayHasKey('flow_context', $tags);
        self::assertSame('Production/NetlogixSentry', $tags['flow_context']);
    }

    /**
     * @test
     */
    public function php_version_is_extracted(): void
    {
        $flowEnvironment = new FlowEnvironment($this->environment);

        $tags = $flowEnvironment->getTags();
        self::assertArrayHasKey('php_version', $tags);
        self::assertSame(PHP_VERSION, $tags['php_version']);
    }

    /**
     * @test
     */
    public function flow_version_is_extracted(): void
    {
        $flowEnvironment = new FlowEnvironment($this->environment);

        $tags = $flowEnvironment->getTags();
        self::assertArrayHasKey('flow_version', $tags);
        self::assertSame(FLOW_VERSION_BRANCH, $tags['flow_version']);
    }

}
