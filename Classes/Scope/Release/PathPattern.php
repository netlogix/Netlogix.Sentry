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
     * @Flow\InjectConfiguration(package="Netlogix.Sentry", path="release.pathPattern")
     * @var string
     */
    protected $pathPattern;

    public function getRelease(): ?string
    {
        $flowRootPath = trim(realpath(FLOW_PATH_ROOT), '/');
        if (preg_match($this->pathPattern, $flowRootPath, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

}
