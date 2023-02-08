<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\LoggingRule;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\LoggingRule\AllowListRule;

class AllowListRuleTest extends UnitTestCase
{
    /**
     * @test
     */
    public function if_allow_list_contains_class_decision_should_be_true(): void
    {
        $allowListRule = $this->getAccessibleMock(AllowListRule::class, ['dummy']);
        $allowListRule->_set('allowList', [
            Test::class
        ]);

        self::assertEquals(true, $allowListRule->decide(new Test(), false));
    }

    /**
     * @test
     */
    public function if_allow_list_not_contains_class_decision_should_be_equal_to_previous_decision(): void
    {
        $allowListRule = $this->getAccessibleMock(AllowListRule::class, ['dummy']);
        $allowListRule->_set('allowList', []);

        $previousDecision = false;

        self::assertEquals($previousDecision, $allowListRule->decide(new Test(), $previousDecision));
    }
}
