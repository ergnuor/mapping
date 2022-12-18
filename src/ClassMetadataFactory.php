<?php

declare(strict_types=1);

namespace Ergnuor\Mapping;

/**
 * @template T
 */
class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    private const ALL_CLASS_NAMES_CACHE_KEY = 'allClassNames';
    private const ALL_CLASS_NAMES_ARRAY_KEY = 'classNames';
    private const ALL_FLIPPPED_CLASS_NAMES_ARRAY_KEY = 'flippedClassNames';

    private bool $isInitialized = false;
    private \Psr\Cache\CacheItemPoolInterface $cache;
    private ?array $classNames = null;
    private ?array $flippedClassNames = null;

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

    public function getAllMetadata(): array
    {
        $metadata = [];
        $classNames = $this->getAllClassNames();

        foreach ($classNames as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
    }

    public function getAllClassNames(): array
    {
        $this->initAllClassNames();

        return $this->classNames;
    }

    private function initAllClassNames(): void
    {
        if ($this->classNames !== null) {
            return;
        }

        $cacheKey = self::ALL_CLASS_NAMES_CACHE_KEY;
        $allClassesCacheItem = $this->cache->getItem($cacheKey);

        if ($allClassesCacheItem->isHit()) {
            $cachedClassNames = $allClassesCacheItem->get();

            $this->classNames = $cachedClassNames[self::ALL_CLASS_NAMES_ARRAY_KEY];
            $this->flippedClassNames = $cachedClassNames[self::ALL_FLIPPPED_CLASS_NAMES_ARRAY_KEY];
        } else {
            $this->classNames = $this->adapter->getClassNames();
            $this->flippedClassNames = array_flip($this->classNames);

//            $cachedMetadataItem = $this->cache->getItem($cacheKey);
            $allClassesCacheItem->set([
                self::ALL_CLASS_NAMES_ARRAY_KEY => $this->classNames,
                self::ALL_FLIPPPED_CLASS_NAMES_ARRAY_KEY => $this->flippedClassNames,
            ]);
            $this->cache->save($allClassesCacheItem);
        }
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

    public function hasMetadataFor(string $className): bool
    {
        $this->initAllClassNames();

        return isset($this->flippedClassNames[$className]);
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