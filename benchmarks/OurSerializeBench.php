<?php

namespace Ntzm\Benchmarks\Serializer;

use Ntzm\Serializer\Serializer;

final class OurSerializeBench extends Bench
{
    protected function serialize($value): void
    {
        (new Serializer())->serialize($value);
    }
}
