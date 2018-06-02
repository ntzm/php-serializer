<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

use Serializable;

class ClassWithSerializable implements Serializable
{
    public function serialize()
    {
        return 'foo';
    }

    public function unserialize($serialized): void
    {
    }
}
