<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Environment;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\Environment\FlowSettings;

class FlowSettingsTest extends UnitTestCase
{

    /**
     * @test
     */
    public function if_no_setting_is_given_null_is_returned(): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([]);

        self::assertNull($flowSettings->getEnvironment());
    }

    /**
     * @test
     */
    public function environment_is_extracted_from_setting(): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([
            'environment' => [
                'setting' => 'my-machine'
            ]
        ]);

        self::assertSame('my-machine', $flowSettings->getEnvironment());
    }

    /**
     * @test
     * @dataProvider provideEmptyEnvironments
     */
    public function empty_environments_are_converted_to_null($environment): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([
            'environment' => [
                'setting' => $environment
            ]
        ]);

        self::assertNull($flowSettings->getEnvironment());
    }

    public static function provideEmptyEnvironments(): iterable
    {
        yield 'Empty String' => ['environment' => ''];
        yield 'NULL' => ['environment' => null];
    }

}
