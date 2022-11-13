<?php

declare(strict_types=1);

namespace Ergnuor\Mapping;

use Psr\Cache\CacheItemPoolInterface;

/**
 * @template T
 */
interface ClassMetadataFactoryInterface
{
    public function getAllMetadata();

    public function getAllClassNames(): array;

    /**
     * @param string $className
     * @return T
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMetadataFor(string $className);

    public function setCache(CacheItemPoolInterface $cache);

    public function getLoadedMetadata(): array;
}