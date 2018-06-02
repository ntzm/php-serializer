<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithTraitWithProperties
{
    use TraitWithProperties;

    public $a = 1;
    protected $b = 2;
    private $c = 3;
}
