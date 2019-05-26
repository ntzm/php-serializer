<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

use Serializable;

class ClassWithSleepAndSerializable implements Serializable
{
    public function serialize()
    {
        return 'foo';
    }

    public function unserialize($serialized): void
    {
    }

    public function __sleep()
    {
        return [
            'a',
            'c',
        ];
    }
}
