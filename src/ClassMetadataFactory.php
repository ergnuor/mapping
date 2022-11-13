<?php

declare(strict_types=1);

namespace Ergnuor\Mapping;

/**
 * @template T
 */
class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    private bool $isInitialized = false;
    private \Psr\Cache\CacheItemPoolInterface $cache;
    private ?array $classNames = null;

    /**
     * @var T[]
     */
    private array $loadedMetadata = [];
    private ClassMetadataFactoryAdapterInterface $adapter;

    public function __construct(
        ClassMetadataFactoryAdapterInterface $adapter
    ) {
        $this->adapter = $adapter;
    }

    public function getAllMetadata()
    {
        $classNames = $this->getAllClassNames();

        foreach ($classNames as $className) {
            $this->getMetadataFor($className);
        }
    }

    public function getAllClassNames(): array
    {
        if ($this->classNames === null) {
            $this->classNames = $this->adapter->getClassNames();
        }

        return $this->classNames;
    }

    /**
     * @param string $className
     * @return T
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMetadataFor(string $className)
    {
        $this->initialize();

        if (isset($this->loadedMetadata[$className])) {
            return $this->loadedMetadata[$className];
        }

        $cacheKey = $this->getCacheKey($className);
        $cachedMetadata = $this->cache->getItem($cacheKey)->get();

        if ($this->adapter->isCorrectCachedInstance($cachedMetadata)) {
            $this->loadedMetadata[$className] = $cachedMetadata;

            $this->adapter->afterGotFromCache($cachedMetadata);
        } else {
            $classMetadata = $this->adapter->loadMetadata($className);

            $cachedMetadataItem = $this->cache->getItem($cacheKey);
            $cachedMetadataItem->set($classMetadata);
            $this->cache->save($cachedMetadataItem);

            $this->adapter->afterMetadataLoaded($classMetadata);

            $this->loadedMetadata[$className] = $classMetadata;
        }


        return $this->loadedMetadata[$className];
    }

    private function initialize()
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;
    }

    private function getCacheKey(string $className): string
    {
        return str_replace('\\', '__', $className);
    }

    public function setCache(\Psr\Cache\CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getLoadedMetadata(): array
    {
        return $this->loadedMetadata;
    }
}