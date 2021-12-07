<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\ExceptionHandler;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;

class ExceptionRenderingOptionsResolverTest extends UnitTestCase
{

    /**
     * @test
     */
    public function If_no_renderingGroups_have_been_defined_an_empty_array_is_returned(): void
    {
        $resolver = new ExceptionRenderingOptionsResolver();

        $result = $resolver->resolveRenderingOptionsForThrowable(self::createThrowable());

        self::assertEmpty($result);
    }

    /**
     * @test
     */
    public function Exception_Code_is_used_to_match_rendering_Groups(): void
    {
        $resolver = new ExceptionRenderingOptionsResolver();

        $resolver->setOptions([
            'renderingGroups' => [
                'someGroup' => [
                    'matchingExceptionCodes' => [self::code()],
                    'options' => [
                        'foo' => 'bar'
                    ]
                ]
            ]
        ]);

        $result = $resolver->resolveRenderingOptionsForThrowable(self::createThrowable());

        self::assertArrayHasKey('renderingGroup', $result);
        self::assertEquals('someGroup', $result['renderingGroup']);

        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    private static function createThrowable(): \Throwable
    {
        return new \Exception('foo', self::code());
    }

    private static function code(): int
    {
        return 1638882641;
    }

}
