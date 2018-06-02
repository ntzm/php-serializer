<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithSleep
{
    public $a = 1;
    protected $b = 'two';
    private $c = ['three', 3];

    public static $d = 4;
    protected static $e = 'five';
    private static $f = ['six', 6];

    public function __sleep()
    {
        return [
            'a',
            'c',
        ];
    }
}
