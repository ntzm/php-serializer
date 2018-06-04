<?php

declare(strict_types=1);

namespace Ntzm\Serializer;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Serializable;
use function count;
use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_nan;
use function is_object;
use function is_resource;
use function is_string;
use function sprintf;
use function strlen;

final class Serializer implements SerializerInterface
{
    private const NULL = 'N;';
    private const TRUE = 'b:1;';
    private const FALSE = 'b:0;';
    private const NAN = 'd:NAN;';
    private const INF = 'd:INF;';
    private const NEGATIVE_INF = 'd:-INF;';
    private const RESOURCE = 'i:0;';

    /** @throws Exception */
    public function serialize($value): string
    {
        if ($value instanceof Closure) {
            throw new Exception("Serialization of 'Closure' is not allowed");
        }

        if ($value === null) {
            return self::NULL;
        }

        if ($value === true) {
            return self::TRUE;
        }

        if ($value === false) {
            return self::FALSE;
        }

        if (is_string($value)) {
            return $this->serializeString($value);
        }

        if (is_int($value)) {
            return $this->serializeInt($value);
        }

        if (is_float($value)) {
            return $this->serializeFloat($value);
        }

        if (is_array($value)) {
            return $this->serializeArray($value);
        }

        if (is_object($value)) {
            return $this->serializeObject($value);
        }

        if (is_resource($value)) {
            return self::RESOURCE;
        }
    }

    private function serializeString(string $string): string
    {
        return sprintf('s:%d:"%s";', strlen($string), $string);
    }

    private function serializeInt(int $int): string
    {
        return sprintf('i:%d;', $int);
    }

    private function serializeFloat(float $float): string
    {
        if ($float === INF) {
            return self::INF;
        }

        if ($float === -INF) {
            return self::NEGATIVE_INF;
        }

        if (is_nan($float)) {
            return self::NAN;
        }

        // todo test this works properly
        $previous = ini_set('precision', ini_get('serialize_precision'));

        $result = "d:${float};";

        ini_set('precision', $previous);

        return $result;
    }

    /** @throws Exception */
    private function serializeArray(array $array): string
    {
        $inner = '';

        foreach ($array as $key => $value) {
            $reference = $this->getReferencePosition($array, $key);

            $inner .= $this->serialize($key);

            if ($reference === null) {
                $inner .= $this->serialize($value);
            } else {
                $inner .= "R:{$reference};";
            }
        }

        return sprintf('a:%d:{%s}', count($array), $inner);
    }

    private function getReferencePosition(array $array, $key): ?int
    {
        // Start at position 2, as position 1 is the array itself
        $position = 2;

        foreach ($array as $i => $item) {
            if ($i === $key) {
                ++$position;

                continue;
            }

            if ($array[$i] !== $array[$key]) {
                ++$position;

                continue;
            }

            // Get the initial value of the element
            $initial = $array[$key];

            // Set it to a new object, as objects only pass === tests if they
            // are the same instance
            $array[$key] = new class() {
            };

            // If the current item is the same instance that we just set, we
            // know they are referencing each other
            if ($array[$i] === $array[$key]) {
                $array[$key] = $initial;

                return $position;
            }

            $array[$key] = $initial;
            ++$position;
        }

        return null;
    }

    /** @throws Exception */
    private function serializeObject(object $object): string
    {
        $reflection = new ReflectionObject($object);
        $className = $reflection->getName();

        if (strpos($className, 'class@anonymous') === 0) {
            throw new Exception("Serialization of 'class@anonymous' is not allowed");
        }

        if ($object instanceof Serializable) {
            $inner = $object->serialize();

            return sprintf(
                'C:%d:"%s":%d:{%s}',
                strlen($className),
                $className,
                strlen($inner),
                $inner
            );
        }

        /** @var ReflectionProperty[] $properties */
        $properties = array_filter(
            $reflection->getProperties(),
            function (ReflectionProperty $property): bool {
                return !$property->isStatic();
            }
        );

        $parent = $reflection->getParentClass();
        $privateParentProperties = [];

        if ($parent instanceof ReflectionClass) {
            /** @var ReflectionProperty[] $privateParentProperties */
            $privateParentProperties = array_filter(
                $parent->getProperties(),
                function (ReflectionProperty $property): bool {
                    return $property->isPrivate() && !$property->isStatic();
                }
            );
        }

        if ($reflection->hasMethod('__sleep')) {
            $propertiesToKeep = $object->__sleep();

            if (!is_array($propertiesToKeep)) {
                trigger_error(
                    '__sleep should return an array only containing the names of instance-variables to serialize'
                );
            }

            $nonExistentProperties = array_diff(
                $propertiesToKeep,
                array_map(function (ReflectionProperty $property): string {
                    return $property->getName();
                }, $properties)
            );

            if (count($nonExistentProperties) !== 0) {
                trigger_error(
                    sprintf('"%s" returned as member variable from __sleep() but does not exist', reset($nonExistentProperties))
                );
            }

            $properties = array_filter(
                $properties,
                function (ReflectionProperty $property) use ($propertiesToKeep): bool {
                    return in_array($property->getName(), $propertiesToKeep, true);
                }
            );
        }

        /** @var ReflectionProperty[] $serializableProperties */
        $serializableProperties = array_merge($properties, $privateParentProperties);

        $inner = '';

        foreach ($serializableProperties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();

            if ($property->isProtected()) {
                $propertyName = "\0*\0${propertyName}";
            } elseif ($property->isPrivate()) {
                $propertyName = "\0{$property->getDeclaringClass()->getName()}\0${propertyName}";
            }

            $inner .= $this->serialize($propertyName);

            $value = $property->getValue($object);

            if ($value === $object) {
                // todo how does this work
                $inner .= 'r:1;';
            } else {
                $inner .= $this->serialize($value);
            }
        }

        return sprintf(
            'O:%d:"%s":%d:{%s}',
            strlen($className),
            $className,
            count($serializableProperties),
            $inner
        );
    }
}
