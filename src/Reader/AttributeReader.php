<?php

declare(strict_types=1);

namespace Ergnuor\Mapping\Reader;

use Attribute;
use Ergnuor\Mapping\Annotation\AnnotationInterface;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function is_string;
use function is_subclass_of;
use function sprintf;

/** @internal */
final class AttributeReader
{
    /** @var array<class-string<AnnotationInterface>,bool> */
    private array $isRepeatableAttribute = [];

    /**
     * @psalm-return class-string-map<T, T|RepeatableAttributeCollection<T>>
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    public function getClassAnnotations(ReflectionClass $class): array
    {
//        dd(
//            $class->getAttributes(),
//            $this->convertToAttributeInstances($class->getAttributes())
//        );

        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @return class-string-map<T, T|RepeatableAttributeCollection<T>>
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /**
     * @return class-string-map<T, T|RepeatableAttributeCollection<T>>
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    public function getPropertyAnnotations(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @param class-string<T> $annotationName The name of the annotation.
     *
     * @return T|null
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        if ($this->isRepeatable($annotationName)) {
            throw new LogicException(sprintf(
                'The attribute "%s" is repeatable. Call getPropertyAnnotationCollection() instead.',
                $annotationName
            ));
        }

        return $this->getPropertyAnnotations($property)[$annotationName]
            ?? ($this->isRepeatable($annotationName) ? new RepeatableAttributeCollection() : null);
    }

    /**
     * @param class-string<T> $annotationName The name of the annotation.
     *
     * @return RepeatableAttributeCollection<T>
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    public function getPropertyAnnotationCollection(
        ReflectionProperty $property,
        string $annotationName
    ): RepeatableAttributeCollection {
        if (! $this->isRepeatable($annotationName)) {
            throw new LogicException(sprintf(
                'The attribute "%s" is not repeatable. Call getPropertyAnnotation() instead.',
                $annotationName
            ));
        }

        return $this->getPropertyAnnotations($property)[$annotationName] ?? new RepeatableAttributeCollection();
    }

    /**
     * @param array<ReflectionAttribute> $attributes
     *
     * @return class-string-map<T, T|RepeatableAttributeCollection<T>>
     *
     * @template T of \Ergnuor\Mapping\Annotation\AnnotationInterface
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Doctrine Annotations
            if (! is_subclass_of($attributeName, AnnotationInterface::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof AnnotationInterface);

            if ($this->isRepeatable($attributeName)) {
                if (! isset($instances[$attributeName])) {
                    $instances[$attributeName] = new RepeatableAttributeCollection();
                }

                $collection = $instances[$attributeName];
                assert($collection instanceof RepeatableAttributeCollection);
                $collection[] = $instance;
            } else {
                $instances[$attributeName] = $instance;
            }
        }

        return $instances;
    }

    /** @param class-string<\Ergnuor\Mapping\Annotation\AnnotationInterface> $attributeClassName */
    private function isRepeatable(string $attributeClassName): bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new ReflectionClass($attributeClassName);
        $attribute       = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = ($attribute->flags & Attribute::IS_REPEATABLE) > 0;
    }

    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        // TODO: Implement getClassAnnotation() method.
    }

    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        // TODO: Implement getMethodAnnotation() method.
    }
}
