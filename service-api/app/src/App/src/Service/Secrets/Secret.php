<?php

declare(strict_types=1);

namespace App\Service\Secrets;

use ParagonIE\HiddenString\HiddenString;

class Secret
{
    public function __construct(readonly public HiddenString $secret)
    {
    }
}
