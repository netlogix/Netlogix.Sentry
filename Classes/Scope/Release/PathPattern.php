<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Release;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class PathPattern implements ReleaseProvider
{

    /**
     * @var string
     */
    protected $pathToMatch;

    /**
     * @var string
     */
    protected $pathPattern;

    public function getRelease(): ?string
    {
        $path = trim(realpath($this->pathToMatch), '/');
        if (@preg_match($this->pathPattern, $path, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function injectSettings(array $settings): void
    {
        $release = $settings['release'] ?? [];
        $this->pathToMatch = $release['pathToMatch'] ?? FLOW_PATH_ROOT;
        $this->pathPattern = $release['pathPattern'] ?? '';
    }

}
