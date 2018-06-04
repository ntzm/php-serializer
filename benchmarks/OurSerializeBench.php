<?php

declare(strict_types=1);

namespace Ntzm\Benchmarks\Serializer;

use Ntzm\Serializer\Serializer;

final class OurSerializeBench extends Bench
{
    protected function serialize($value): void
    {
        (new Serializer())->serialize($value);
    }
}
