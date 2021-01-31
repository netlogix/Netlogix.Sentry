<?php
declare(strict_types=1);

namespace Netlogix\Sentry\Scope;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Netlogix\Sentry\Exception\InvalidProviderType;
use Netlogix\Sentry\Scope\Environment\EnvironmentProvider;
use Netlogix\Sentry\Scope\Extra\ExtraProvider;
use Netlogix\Sentry\Scope\Release\ReleaseProvider;
use Netlogix\Sentry\Scope\Tags\TagProvider;
use Netlogix\Sentry\Scope\User\UserProvider;

/**
 * @Flow\Scope("singleton")
 */
class ScopeProvider
{

    private const SCOPE_ENVIRONMENT = 'environment';
    private const SCOPE_EXTRA = 'extra';
    private const SCOPE_RELEASE = 'release';
    private const SCOPE_TAGS = 'tags';
    private const SCOPE_USER = 'user';

    private const SCOPE_TYPE_MAPPING = [
        self::SCOPE_ENVIRONMENT => EnvironmentProvider::class,
        self::SCOPE_EXTRA => ExtraProvider::class,
        self::SCOPE_RELEASE => ReleaseProvider::class,
        self::SCOPE_TAGS => TagProvider::class,
        self::SCOPE_USER => UserProvider::class,
    ];

    /**
     * @var array
     */
    protected $providerConfiguration;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array<string, array<string, object>>
     */
    protected $providers = [
        self::SCOPE_ENVIRONMENT => [],
        self::SCOPE_EXTRA => [],
        self::SCOPE_RELEASE => [],
        self::SCOPE_TAGS => [],
        self::SCOPE_USER => [],
    ];

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function initializeObject(): void
    {
        $this->setupProviders();
    }

    public function collectEnvironment(): ?string
    {
        $environment = null;

        foreach ($this->providers[self::SCOPE_ENVIRONMENT] as $provider) {
            assert($provider instanceof EnvironmentProvider);

            $environment = $provider->getEnvironment();
        }

        return $environment;
    }

    public function collectExtra(): array
    {
        $extra = [];

        foreach ($this->providers[self::SCOPE_EXTRA] as $provider) {
            assert($provider instanceof ExtraProvider);

            $extra = array_merge_recursive($extra, $provider->getExtra());
        }

        return $extra;
    }

    public function collectRelease(): ?string
    {
        $release = null;

        foreach ($this->providers[self::SCOPE_RELEASE] as $provider) {
            assert($provider instanceof ReleaseProvider);

            $release = $provider->getRelease();
        }

        return $release;
    }

    public function collectTags(): array
    {
        $tags = [];

        foreach ($this->providers[self::SCOPE_TAGS] as $provider) {
            assert($provider instanceof TagProvider);

            $tags = array_merge($tags, $provider->getTags());
        }

        return $tags;
    }

    public function collectUser(): array
    {
        $user = [];

        foreach ($this->providers[self::SCOPE_USER] as $provider) {
            assert($provider instanceof UserProvider);

            $user = array_merge_recursive($user, $provider->getUser());
        }

        return $user;
    }

    public function injectSettings(array $settings): void
    {
        $this->providerConfiguration = $settings['scope'] ?? [];
    }

    protected function setupProviders(): void
    {
        $scopes = array_keys($this->providers);

        foreach ($scopes as $scope) {
            $this->providers[$scope] = [];
            if (!array_key_exists($scope, $this->providerConfiguration)) {
                continue;
            }

            $providers = $this->providerConfiguration[$scope];
            $providers = array_filter($providers);
            $providers = array_map(function ($position) {
                if ($position === true) {
                    // if no position string is given, sort near end
                    $position = 'end';
                }

                return ['position' => $position];
            }, $providers);

            $providersClassNames = (new PositionalArraySorter($providers, 'position'))->getSortedKeys();

            foreach ($providersClassNames as $providerClassName) {
                if (!is_a($providerClassName, self::SCOPE_TYPE_MAPPING[$scope], true)) {
                    throw new InvalidProviderType(
                        sprintf(
                            'Provider "%s" for scope "%s" must be of type "%s"',
                            $providerClassName,
                            $scope,
                            self::SCOPE_TYPE_MAPPING[$scope]
                        ),
                        1612043813
                    );
                }

                $instance = $this->objectManager->get($providerClassName);
                $this->providers[$scope][$providerClassName] = $instance;
            }
        }
    }

}
