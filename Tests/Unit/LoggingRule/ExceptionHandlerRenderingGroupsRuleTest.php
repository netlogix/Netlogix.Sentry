<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\LoggingRule;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\ExceptionHandler\ExceptionRenderingOptionsResolver;
use Netlogix\Sentry\LoggingRule\ExceptionHandlerRenderingGroupsRule;

class ExceptionHandlerRenderingGroupsRuleTest extends UnitTestCase
{
    /**
     * @test
     */
    public function if_log_exception_is_true_decision_should_be_true(): void
    {
        $optionsResolver = new ExceptionRenderingOptionsResolver();
        $optionsResolver->setOptions([
            'renderingGroups' => [
                'netlogixSentryTest' => [
                    'matchingExceptionClassNames' => [Test::class],
                    'options' => [
                        'logException' => true
                    ]
                ]
            ]
        ]);

        $exceptionHandlerRenderingGroupsRule = $this->getAccessibleMock(ExceptionHandlerRenderingGroupsRule::class, ['dummy']);
        $exceptionHandlerRenderingGroupsRule->_set('optionsResolver', $optionsResolver);

        self::assertTrue($exceptionHandlerRenderingGroupsRule->decide(new Test(), false));
    }

    /**
     * @test
     */
    public function if_log_exception_is_false_decision_should_be_false(): void
    {
        $optionsResolver = new ExceptionRenderingOptionsResolver();
        $optionsResolver->setOptions([
            'renderingGroups' => [
                'netlogixSentryTest' => [
                    'matchingExceptionClassNames' => [Test::class],
                    'options' => [
                        'logException' => false
                    ]
                ]
            ]
        ]);

        $exceptionHandlerRenderingGroupsRule = $this->getAccessibleMock(ExceptionHandlerRenderingGroupsRule::class, ['dummy']);
        $exceptionHandlerRenderingGroupsRule->_set('optionsResolver', $optionsResolver);

        self::assertFalse($exceptionHandlerRenderingGroupsRule->decide(new Test(), true));
    }

    /**
     * @test
     */
    public function if_log_exception_is_null_decision_should_be_equal_to_previous_decision(): void
    {
        $optionsResolver = new ExceptionRenderingOptionsResolver();
        $optionsResolver->setOptions([
            'renderingGroups' => [
                'netlogixSentryTest' => [
                    'matchingExceptionClassNames' => [Test::class],
                    'options' => []
                ]
            ]
        ]);

        $exceptionHandlerRenderingGroupsRule = $this->getAccessibleMock(ExceptionHandlerRenderingGroupsRule::class, ['dummy']);
        $exceptionHandlerRenderingGroupsRule->_set('optionsResolver', $optionsResolver);

        $previousDecision = true;

        self::assertEquals(
            $previousDecision,
            $exceptionHandlerRenderingGroupsRule->decide(new Test(), $previousDecision)
        );
    }
}
