<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithProperties
{
    public const A = 1;
    protected const B = 2;
    private const C = 3;

    public $a = 1;
    protected $b = 'two';
    private $g = 1;
    private $c = ['three', 3];

    public static $d = 4;
    protected static $e = 'five';
    private static $f = ['six', 6];

    public function setG($value): self
    {
        $this->g = $value;

        return $this;
    }
}
