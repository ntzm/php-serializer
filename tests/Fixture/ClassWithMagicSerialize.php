<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithMagicSerialize
{
    public function __serialize()
    {
        return [
            'foo' => 'bar',
            1 => 2,
        ];
    }
}
