<?php
declare(strict_types=1);

namespace Netlogix\Sentry\ThrowableStorage;

use Closure;
use InvalidArgumentException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\ThrowableStorageInterface;
use RuntimeException;
use Throwable;

/**
 * @Flow\Proxy(false)
 * @Flow\Autowiring(false)
 */
final class CompoundStorage implements ThrowableStorageInterface
{

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var Closure
     */
    private $initializeStoragesClosure;

    /**
     * @var Closure
     */
    protected $requestInformationRenderer;

    /**
     * @var Closure
     */
    protected $backtraceRenderer;

    /**
     * @var ThrowableStorageInterface
     */
    private $primaryStorage;

    /**
     * @var array<ThrowableStorageInterface>
     */
    private $additionalStorages;

    public static function createWithOptions(array $options): ThrowableStorageInterface
    {
        $storagesFromOptions = $options['storages'] ?? [];
        if ($storagesFromOptions === []) {
            throw new InvalidArgumentException('No storages passed to CompoundStorage', 1612095040);
        }
        $storageClassNames = [];

        foreach ($storagesFromOptions as $storageClassName) {
            if (!is_a($storageClassName, ThrowableStorageInterface::class, true)) {
                throw new InvalidArgumentException(sprintf('Class "%s" must implement ThrowableStorageInterface',
                    $storageClassName), 1612095174);
            }
            if (is_a($storageClassName, CompoundStorage::class, true)) {
                throw new InvalidArgumentException('Cannot use CompoundStorage as Storage for CompoundStorage',
                    1612096699);
            }
            $storageClassNames[] = $storageClassName;
        }

        $primaryStorageClassName = array_shift($storageClassNames);

        return new CompoundStorage($primaryStorageClassName, ... $storageClassNames);
    }

    private function __construct(string $primaryStorageClassName, string ...$additionalStorageClassNames)
    {
        $this->initializeStoragesClosure = function () use ($primaryStorageClassName, $additionalStorageClassNames) {
            $this->primaryStorage = $this->createStorage($primaryStorageClassName);
            $this->additionalStorages = [];

            foreach ($additionalStorageClassNames as $additionalStorageClassName) {
                $this->additionalStorages[] = $this->createStorage($additionalStorageClassName);
            }
        };
    }

    public function logThrowable(Throwable $throwable, array $additionalData = [])
    {
        if (!$this->initializeStorages()) {
            // could not initialize storages, throw exception
            throw $throwable;
        }

        $message = $this->primaryStorage->logThrowable($throwable, $additionalData);

        array_walk($this->additionalStorages,
            static function (ThrowableStorageInterface $storage) use ($throwable, $additionalData) {
                $storage->logThrowable($throwable, $additionalData);
            });

        return $message;
    }

    public function setRequestInformationRenderer(Closure $requestInformationRenderer)
    {
        $this->requestInformationRenderer = $requestInformationRenderer;
    }

    public function setBacktraceRenderer(Closure $backtraceRenderer)
    {
        $this->backtraceRenderer = $backtraceRenderer;
    }

    private function createStorage(string $storageClassName): ThrowableStorageInterface
    {
        if (!Bootstrap::$staticObjectManager) {
            throw new RuntimeException('Bootstrap::$staticObjectManager is not set yet', 1612434395);
        }

        assert(is_a($storageClassName, ThrowableStorageInterface::class, true));
        $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.Flow');
        $storageOptions = $settings['log']['throwables']['optionsByImplementation'][$storageClassName] ?? [];

        $storage = $storageClassName::createWithOptions($storageOptions);
        if (isset($this->requestInformationRenderer)) {
            $storage->setRequestInformationRenderer($this->requestInformationRenderer);
        }
        if (isset($this->backtraceRenderer)) {
            $storage->setBacktraceRenderer($this->backtraceRenderer);
        }

        return $storage;
    }

    private function initializeStorages(): bool
    {
        if ($this->initialized) {
            return true;
        }

        try {
            ($this->initializeStoragesClosure)();
        } catch (Throwable $t) {
            return false;
        }

        $this->initialized = true;

        return true;
    }

}
