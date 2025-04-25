<?php

declare(strict_types=1);

namespace Netlogix\Sentry\ContentSecurityPolicy;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
#[Flow\Scope('singleton')]
class Registry implements ProtectedContextAwareInterface
{
    protected static array $safeInlineScriptHashes = [];

    /**
     * Registers a safe inline script from the provided source.
     *
     * @param string $script The script content that needs to be registered as a safe inline script.
     * @param string $algorithm The algorithm to use for hashing the script content (default: sha256)
     * @return string The trimmed script content is returned
     */
    public function registerSafeInlineScriptFromSource(string $script, string $algorithm = 'sha256'): string
    {
        $hash = base64_encode(hash($algorithm, $script, true));
        self::$safeInlineScriptHashes[] = sprintf('%s-%s', $algorithm, $hash);

        return $script;
    }

    public function getSafeInlineScriptHashes(): array
    {
        return self::$safeInlineScriptHashes;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
