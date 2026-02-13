<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\Scope\Extra;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Scope\Extra\VariablesFromStackProvider;

use function iterator_to_array;

class VariablesFromStackProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function Render_simple_values_as_single_line_information(): void
    {
        $result = iterator_to_array(
            VariablesFromStackProvider::buildReadableRepresentationOfTrace(
                'foo::bar()',
                [
                    'baz' => '0'
                ],
                [
                    'args' => [
                        'some value'
                    ]
                ]
            )
        );

        self::assertEquals([
            [
                'foo::bar()' => [
                    'baz' => 'some value'
                ]
            ]
        ], $result);
    }

    /**
     * @test
     */
    public function Render_complex_values_as_multi_line_information(): void
    {
        $result = iterator_to_array(
            VariablesFromStackProvider::buildReadableRepresentationOfTrace(
                'foo::bar()',
                [
                    'baz.name' => '0.name',
                    'baz.gender' => '0.gender',
                ],
                [
                    'args' => [
                        [
                            'name' => 'Stephan',
                            'gender' => 'male'
                        ]
                    ]
                ]
            )
        );

        self::assertEquals([
            [
                'foo::bar()' => [
                    'baz.name' => 'Stephan',
                    'baz.gender' => 'male',
                ]
            ]
        ], $result);
    }

    /**
     * @test
     */
    public function Render_special_message_when_frames_dont_contain_method_arguments(): void
    {
        $result = iterator_to_array(
            VariablesFromStackProvider::buildReadableRepresentationOfTrace(
                'foo::bar()',
                [
                    'baz' => '0'
                ],
                []
            )
        );

        self::assertEquals([
            [
                'foo::bar()' => [
                    '💥' => 'Argument tracing is disabled. To enable, set `zend.exception_ignore_args=0` ⚙️'
                ]
            ]
        ], $result);
    }
}
