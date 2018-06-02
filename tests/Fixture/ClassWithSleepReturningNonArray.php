<?php

declare(strict_types=1);

namespace Ntzm\Tests\Serializer\Fixture;

class ClassWithSleepReturningNonArray
{
    public function __sleep()
    {
        return null;
    }
}
