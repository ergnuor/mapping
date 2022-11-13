<?php

declare(strict_types=1);

namespace Ergnuor\Mapping\Reader;

use ArrayObject;
use Ergnuor\Mapping\Annotation\AnnotationInterface;

/**
 * @template-extends ArrayObject<int, T>
 * @template T of AnnotationInterface
 */
final class RepeatableAttributeCollection extends ArrayObject
{
}
