<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Release;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\Release\PathPattern;

class PathPatternTest extends UnitTestCase
{

    /**
     * @test
     */
    public function if_no_pattern_is_given_null_is_returned(): void
    {
        $pathPattern = new PathPattern();
        $pathPattern->injectSettings([]);

        self::assertNull($pathPattern->getRelease());
    }

    /**
     * @test
     */
    public function release_is_extracted_from_pathToMatch(): void
    {
        // since PathPattern uses realpath, using vfsStream does not work here :(
        $dir = getcwd() . '/my-application-in-version-123-is-deployed-here';

        try {
            mkdir($dir);

            $pathPattern = new PathPattern();
            $pathPattern->injectSettings([
                'release' => [
                    'pathToMatch' => $dir,
                    'pathPattern' => '%(version-\d+)%'
                ]
            ]);

            self::assertSame('version-123', $pathPattern->getRelease());
        } finally {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    /**
     * @test
     */
    public function if_release_pattern_does_not_match_null_is_returned(): void
    {
        // since PathPattern uses realpath, using vfsStream does not work here :(
        $dir = getcwd() . '/my-application-in-version-foo-is-deployed-here';

        try {
            mkdir($dir);

            $pathPattern = new PathPattern();
            $pathPattern->injectSettings([
                'release' => [
                    'pathToMatch' => $dir,
                    'pathPattern' => '%(version-\d+)%'
                ]
            ]);

            self::assertNull($pathPattern->getRelease());
        } finally {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

}
