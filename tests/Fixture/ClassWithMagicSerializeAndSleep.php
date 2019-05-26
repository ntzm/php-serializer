<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithMagicSerializeAndSleep
{
    public function __serialize()
    {
        return [
            'foo' => 'bar',
            1 => 2,
        ];
    }

    public function __sleep()
    {
        return [
            'a',
            'c',
        ];
    }
}
