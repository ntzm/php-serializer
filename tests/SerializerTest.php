<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer;

use Exception;
use Ntzm\Serializer\Serializer;
use Ntzm\Tests\Serializer\Fixture\ClassWithInheritedProperties;
use Ntzm\Tests\Serializer\Fixture\ClassWithProperties;
use Ntzm\Tests\Serializer\Fixture\ClassWithSerializable;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleep;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleepInParentReferencingPrivateProperty;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleepReturningNonArray;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleepWithNonExistentProperties;
use Ntzm\Tests\Serializer\Fixture\ClassWithTraitWithProperties;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Serializable;
use stdClass;
use function fopen;
use function serialize;

final class SerializerTest extends TestCase
{
    /** @dataProvider provideTestCases */
    public function test($value): void
    {
        self::assertSame(serialize($value), (new Serializer())->serialize($value));
    }

    public function provideTestCases(): array
    {
        $selfReferencingStdClass = new stdClass();
        $selfReferencingStdClass->a = $selfReferencingStdClass;

        $stdClassReferenceInside = new stdClass();
        $stdClassReferenceInside->a = new stdClass();
        $stdClassReferenceInside->b = $stdClassReferenceInside->a;

        $arrayReferenceInside = ['foo', 'bar'];
        $arrayReferenceInside[2] = &$arrayReferenceInside[1];
        $arrayReferenceInside[3] = &$arrayReferenceInside[2];

        return [
            'empty string' => [''],
            'normal string' => ['foo'],
            'string with double quotes' => ['foo"bar'],
            'string with single quotes' => ['foo\'bar'],
            'string with multi-byte characters' => ['测试'],
            'string with emoji' => ['💩💩💩'],
            'string with invalid UTF-8 sequence' => ["\xB1\x31"],

            'integer zero' => [0],
            'integer negative zero' => [-0],
            'normal integer' => [5],
            'normal negative integer' => [-5],
            'minimum integer' => [PHP_INT_MIN],
            'lower than minimum integer' => [-123809128309128301928310293],
            'maximum integer' => [PHP_INT_MAX],
            'higher than maximum integer' => [1238102938102938120938121239],

            'float zero' => [0.0],
            'float with zero decimal' => [1.0],
            'normal float' => [1.1],
            'float with huge precision' => [1.132798123791823791283719283712983712983172938127398],

            'null' => [null],
            'true' => [true],
            'false' => [false],
            'infinity' => [INF],
            'negative infinity' => [-INF],
            'nan' => [NAN],

            'empty array' => [[]],
            'self-referencing array' => [$arrayReferenceInside],

            'instance' => [new ClassWithProperties()],
            'instance with inherited properties 1' => [new ClassWithInheritedProperties()],
            'instance with inherited properties 2' => [(new ClassWithInheritedProperties())->setG('foo')],
            'instance with properties from trait' => [new ClassWithTraitWithProperties()],
            'instance with __sleep' => [new ClassWithSleep()],
            'instance implements serializable' => [new ClassWithSerializable()],
            'stdclass instance' => [(object) ['foo' => (object) ['bar']]],
            'empty stdclass' => [new stdClass()],
            'stdclass reference inside' => [$stdClassReferenceInside],
            'self-referencing stdclass' => [$selfReferencingStdClass],

            'resource' => [fopen(__DIR__.'/Fixture/ClassWithProperties.php', 'rb')],
        ];
    }

    /** @dataProvider provideTestDoesNotSerializeClosuresCases */
    public function testDoesNotSerializeClosures($value): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Serialization of 'Closure' is not allowed");

        (new Serializer())->serialize($value);
    }

    public function provideTestDoesNotSerializeClosuresCases(): array
    {
        return [
            'closure' => [function (): void {}],
            'closure in array' => [[function (): void {}]],
            'closure in object' => [(object) [function (): void {}]],
        ];
    }

    public function testSleepWithNonExistentProperties(): void
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('"d" returned as member variable from __sleep() but does not exist');

        (new Serializer())->serialize(new ClassWithSleepWithNonExistentProperties());
    }

    public function testInheritedSleepReferencingPrivateProperty(): void
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('"c" returned as member variable from __sleep() but does not exist');

        (new Serializer())->serialize(new ClassWithSleepInParentReferencingPrivateProperty());
    }

    public function testSleepReturningNonArray(): void
    {
        $this->expectException(Notice::class);
        $this->expectExceptionMessage('__sleep should return an array only containing the names of instance-variables to serialize');

        (new Serializer())->serialize(new ClassWithSleepReturningNonArray());
    }

    /** @dataProvider provideTestDoesNotSerializeAnonymousClassesCases */
    public function testDoesNotSerializeAnonymousClasses(object $instance): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Serialization of 'class@anonymous' is not allowed");

        (new Serializer())->serialize($instance);
    }

    public function provideTestDoesNotSerializeAnonymousClassesCases(): array
    {
        return [
            'standard' => [new class() {
            }],
            'with sleep' => [
                new class() {
                    public function __sleep(): array
                    {
                        return [];
                    }
                },
            ],
            'implements serializable' => [
                new class() implements Serializable {
                    public function serialize(): void
                    {
                    }

                    public function unserialize($serialized): void
                    {
                    }
                },
            ],
        ];
    }
}
