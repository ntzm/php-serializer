<?php

declare(strict_types=1);

namespace Ntzm\Serializer;

use Exception;

interface SerializerInterface
{
    /** @throws Exception */
    public function serialize($value): string;
}
