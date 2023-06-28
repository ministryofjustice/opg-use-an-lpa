<?php

declare(strict_types=1);

namespace Common\Entity\InstructionsAndPreferences;

class SignedUrl
{
    public function __construct(
        public readonly string $imageName,
        public readonly string $url,
    ) {
    }
}
