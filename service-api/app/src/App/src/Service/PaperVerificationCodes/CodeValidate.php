<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use DateTimeInterface;
use JsonSerializable;

class CodeValidate implements JsonSerializable
{
    public function __construct(
        public readonly string $donorName,
        public readonly LpaType $lpaType,
        public readonly DateTimeInterface $codeExpiryDate,
        public readonly LpaStatus $lpaStatus,
        public readonly LpaSource $lpaSource,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'donorName'  => $this->donorName,
            'type'       => $this->lpaType,
            'expiryDate' => $this->codeExpiryDate->format(DateTimeInterface::ATOM),
            'status'     => $this->lpaStatus,
            'source'     => $this->lpaSource,
        ];
    }
}
