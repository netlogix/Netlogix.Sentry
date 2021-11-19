<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope\Release;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class FlowSettings implements ReleaseProvider
{

    /**
     * @var string|null
     */
    protected $release = null;

    public function getRelease(): ?string
    {
        return $this->release;
    }

    public function injectSettings(array $settings): void
    {
        $release = $settings['release'] ?? [];
        $this->release = (string)($release['setting'] ?? '');
        if ($this->release === '') {
            $this->release = null;
        }
    }

}
