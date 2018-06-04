<?php

declare(strict_types=1);

namespace Ntzm\Benchmarks\Serializer;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

/**
 * @Revs(1000)
 * @Iterations(5)
 */
abstract class Bench
{
    public function provideStrings(): array
    {
        return [
            ['foo'],
            [''],
        ];
    }

    public function provideBooleans(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /** @ParamProviders("provideStrings") */
    public function benchSerializeString(array $params): void
    {
        $this->serialize($params[0]);
    }

    /** @ParamProviders("provideBooleans") */
    public function benchSerializeBoolean(array $params): void
    {
        $this->serialize($params[0]);
    }

    public function benchSerializeNull(): void
    {
        $this->serialize(null);
    }

    abstract protected function serialize($value): void;
}
