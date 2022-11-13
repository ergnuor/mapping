<?php
declare(strict_types=1);

namespace Ergnuor\Mapping;

interface ClassMetadataFactoryAdapterInterface
{
    public function getClassNames(): array;

    public function isCorrectCachedInstance($cachedMetadata): bool;

    public function afterGotFromCache($cachedMetadata): void;

    public function loadMetadata(string $className);

    public function afterMetadataLoaded($cachedMetadata): void;
}