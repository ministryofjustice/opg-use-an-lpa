<?php

declare(strict_types=1);

namespace App\DataAccess\Repository\Response;

class InstructionsAndPreferencesImages
{
    public function __construct(
        public readonly int $uId,
        public readonly InstructionsAndPreferencesImagesResult $status,
        public readonly array $signedUrls,
    ) {
    }
}
