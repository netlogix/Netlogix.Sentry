<?php

declare(strict_types=1);

namespace Netlogix\Sentry\Tests\Unit\LoggingRule;

use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Sentry\Exception\Test;
use Netlogix\Sentry\LoggingRule\DenyListRule;

class DenyListRuleTest extends UnitTestCase
{
    /**
     * @test
     */
    public function if_deny_list_contains_class_decision_should_be_false(): void
    {
        $denyListRule = $this->getAccessibleMock(DenyListRule::class, ['dummy']);
        $denyListRule->_set('denyList', [
            Test::class
        ]);

        self::assertFalse($denyListRule->decide(new Test(), true));
    }

    /**
     * @test
     */
    public function if_deny_list_not_contains_class_decision_should_be_equal_to_previous_decision(): void
    {
        $denyListRule = $this->getAccessibleMock(DenyListRule::class, ['dummy']);
        $denyListRule->_set('denyList', []);

        $previousDecision = true;

        self::assertEquals($previousDecision, $denyListRule->decide(new Test(), $previousDecision));
    }
}
