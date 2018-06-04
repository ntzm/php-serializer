<?php

declare(strict_types=1);

namespace Ntzm\Benchmarks\Serializer;

final class PhpSerializeBench extends Bench
{
    protected function serialize($value): void
    {
        serialize($value);
    }
}
