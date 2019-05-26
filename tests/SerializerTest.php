<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer;

use Exception;
use Generator;
use Ntzm\Serializer\Serializer;
use Ntzm\Tests\Serializer\Fixture\ClassWithInheritedProperties;
use Ntzm\Tests\Serializer\Fixture\ClassWithMagicSerialize;
use Ntzm\Tests\Serializer\Fixture\ClassWithMagicSerializeAndSerializable;
use Ntzm\Tests\Serializer\Fixture\ClassWithMagicSerializeAndSleep;
use Ntzm\Tests\Serializer\Fixture\ClassWithProperties;
use Ntzm\Tests\Serializer\Fixture\ClassWithSerializable;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleep;
use Ntzm\Tests\Serializer\Fixture\ClassWithSleepAndSerializable;
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

    public function provideTestCases(): Generator
    {
        yield 'empty string' => [''];
        yield 'normal string' => ['foo'];
        yield 'string with double quotes' => ['foo"bar'];
        yield 'string with single quotes' => ['foo\'bar'];
        yield 'string with multi-byte characters' => ['测试'];
        yield 'string with emoji' => ['💩💩💩'];
        yield 'string with invalid UTF-8 sequence' => ["\xB1\x31"];

        yield 'integer zero' => [0];
        yield 'integer negative zero' => [-0];
        yield 'normal integer' => [5];
        yield 'normal negative integer' => [-5];
        yield 'minimum integer' => [PHP_INT_MIN];
        yield 'lower than minimum integer' => [-123809128309128301928310293];
        yield 'maximum integer' => [PHP_INT_MAX];
        yield 'higher than maximum integer' => [1238102938102938120938121239];

        yield 'float zero' => [0.0];
        yield 'float with zero decimal' => [1.0];
        yield 'normal float' => [1.1];
        yield 'float with huge precision' => [1.132798123791823791283719283712983712983172938127398];

        yield 'null' => [null];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'infinity' => [INF];
        yield 'negative infinity' => [-INF];
        yield 'nan' => [NAN];

        yield 'empty array' => [[]];

        $arrayReferenceInside = ['foo', 'bar'];
        $arrayReferenceInside[2] = &$arrayReferenceInside[1];
        $arrayReferenceInside[3] = &$arrayReferenceInside[2];

        yield 'self-referencing array' => [$arrayReferenceInside];

        $typed = new class {
            public int $a = 1;
        };

        yield 'array element referencing typed property' => [[&$typed->a]];

        yield 'instance' => [new ClassWithProperties()];
        yield 'instance with inherited properties 1' => [new ClassWithInheritedProperties()];
        yield 'instance with inherited properties 2' => [(new ClassWithInheritedProperties())->setG('foo')];
        yield 'instance with properties from trait' => [new ClassWithTraitWithProperties()];
        yield 'instance with __sleep' => [new ClassWithSleep()];
        yield 'instance with __serialize' => [new ClassWithMagicSerialize()];
        yield 'instance implements serializable' => [new ClassWithSerializable()];
        yield 'instance with __serialize and serializable' => [new ClassWithMagicSerializeAndSerializable()];
        yield 'instance with __serialize and __sleep' => [new ClassWithMagicSerializeAndSleep()];
        yield 'instance with __sleep and serializable' => [new ClassWithSleepAndSerializable()];
        yield 'stdclass instance' => [(object) ['foo' => (object) ['bar']]];
        yield 'empty stdclass' => [new stdClass()];

        $stdClassReferenceInside = new stdClass();
        $stdClassReferenceInside->a = new stdClass();
        $stdClassReferenceInside->b = $stdClassReferenceInside->a;

        yield 'stdclass reference inside' => [$stdClassReferenceInside];

        $selfReferencingStdClass = new stdClass();
        $selfReferencingStdClass->a = $selfReferencingStdClass;

        yield 'self-referencing stdclass' => [$selfReferencingStdClass];
        yield 'incomplete class' => [unserialize('O:3:"Foo":0:{}')];
        yield 'incomplete class with inherited properties' => [unserialize("O:3:\"Baz\":1:{s:8:\"\0Foo\0bar\";i:1;}")];

        yield 'resource' => [fopen(__DIR__.'/Fixture/ClassWithProperties.php', 'r')];
    }

    public function testSerializesClosedResource(): void
    {
        /** @var resource $closedResource */
        $closedResource = fopen(__DIR__.'/Fixture/ClassWithProperties.php', 'r');
        fclose($closedResource);

        self::assertSame(serialize($closedResource), (new Serializer())->serialize($closedResource));
    }

    /** @dataProvider provideTestDoesNotSerializeClosuresCases */
    public function testDoesNotSerializeClosures($value): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Serialization of 'Closure' is not allowed");

        (new Serializer())->serialize($value);
    }

    public function provideTestDoesNotSerializeClosuresCases(): Generator
    {
        yield 'closure' => [function (): void {}];
        yield 'closure in array' => [[function (): void {}]];
        yield 'closure in object' => [(object) [function (): void {}]];
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

    public function provideTestDoesNotSerializeAnonymousClassesCases(): Generator
    {
        yield 'standard' => [
            new class() {
            },
        ];

        yield 'with sleep' => [
            new class() {
                public function __sleep(): array
                {
                    return [];
                }
            },
        ];

        yield 'implements serializable' => [
            new class() implements Serializable {
                public function serialize(): void
                {
                }

                public function unserialize($serialized): void
                {
                }
            },
        ];
    }
}
