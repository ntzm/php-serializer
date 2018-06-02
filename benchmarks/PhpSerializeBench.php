<?php

namespace Ntzm\Benchmarks\Serializer;

final class PhpSerializeBench extends Bench
{
    protected function serialize($value): void
    {
        serialize($value);
    }
}
