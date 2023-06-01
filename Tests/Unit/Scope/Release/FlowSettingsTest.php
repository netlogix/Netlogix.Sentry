<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Release;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\Release\FlowSettings;

class FlowSettingsTest extends UnitTestCase
{

    /**
     * @test
     */
    public function if_no_setting_is_given_null_is_returned(): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([]);

        self::assertNull($flowSettings->getRelease());
    }

    /**
     * @test
     */
    public function release_is_extracted_from_setting(): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([
            'release' => [
                'setting' => 'latest'
            ]
        ]);

        self::assertSame('latest', $flowSettings->getRelease());
    }

    /**
     * @test
     * @dataProvider provideEmptyReleases
     */
    public function empty_releases_are_converted_to_null($release): void
    {
        $flowSettings = new FlowSettings();
        $flowSettings->injectSettings([
            'release' => [
                'setting' => $release
            ]
        ]);

        self::assertNull($flowSettings->getRelease());
    }

    public static function provideEmptyReleases(): iterable
    {
        yield 'Empty String' => ['release' => ''];
        yield 'NULL' => ['release' => null];
    }

}
