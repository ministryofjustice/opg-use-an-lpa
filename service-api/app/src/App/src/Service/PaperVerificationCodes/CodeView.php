<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Entity\Lpa;
use App\Enum\LpaSource;
use JsonSerializable;

readonly class CodeView implements JsonSerializable
{
    public function __construct(
        public readonly LpaSource $lpaSource,
        public readonly Lpa $lpa,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'source' => $this->lpaSource,
            'lpa'    => $this->lpa,
        ];
    }
}
