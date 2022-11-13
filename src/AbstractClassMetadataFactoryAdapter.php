<?php
declare(strict_types=1);

namespace Ergnuor\Mapping;

use Ergnuor\Mapping\Reader\AttributeReader;

abstract class AbstractClassMetadataFactoryAdapter implements ClassMetadataFactoryAdapterInterface
{
    protected AttributeReader $reader;
    protected array $entityDirs;

    public function __construct(
        array $entityDirs
    ) {
        $this->reader = new AttributeReader();
        $this->setEntityDirs($entityDirs);
    }

    private function setEntityDirs(array $entityDirs): void
    {
        if (empty($entityDirs)) {
            throw new \RuntimeException('Entity dirs can not be empty');
        }

        $this->entityDirs = $entityDirs;
    }

    public function getClassNames(): array
    {
        $classes = [];
        $includedFiles = [];

        foreach ($this->entityDirs as $entityDir) {

            if (!is_dir($entityDir)) {
                throw new \RuntimeException("Directory is not exists '{$entityDir}");
            }

//        $iterator = new \RegexIterator(
//            new \DirectoryIterator($entityDir),
//            '/^.+.php$/i',
//            \RecursiveRegexIterator::GET_MATCH
//        );

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($entityDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

//        $iterator =  new \RecursiveIteratorIterator(
//                new \RecursiveDirectoryIterator($entityDir, \FilesystemIterator::SKIP_DOTS),
//                \RecursiveIteratorIterator::LEAVES_ONLY
//            );

//        $iterator = new \DirectoryIterator($entityDir);

            foreach ($iterator as $file) {
//            $sourceFile = $entityDir . '/' . $file[0];
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

//            dd($sourceFile);

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

//        dd($includedFiles);

        $declared = get_declared_classes();

//        dd($declared);

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (
                $rc->isInterface() ||
                !in_array($sourceFile, $includedFiles) ||
                $this->isTransient($className)
            ) {
                continue;
            }

            $classes[] = $className;
        }

        return $classes;
    }

    abstract protected function isTransient(string $className): bool;
}