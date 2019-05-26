<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

use Serializable;

class ClassWithMagicSerializeAndSerializable implements Serializable
{
    public function __serialize()
    {
        return [
            1 => 2,
        ];
    }

    public function serialize()
    {
        return 'foo';
    }

    public function unserialize($serialized): void
    {
    }
}
