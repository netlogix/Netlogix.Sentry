<?php

declare(strict_types=1);

namespace Netlogix\Sentry\EventProcessor;

use Sentry\Event;
use Sentry\EventHint;

interface EventProcessor
{
    public function rewriteEvent(Event $event, EventHint $hint): Event;
}
