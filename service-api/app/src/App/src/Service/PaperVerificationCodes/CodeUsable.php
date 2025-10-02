<?php

declare(strict_types=1);

namespace App\Service\PaperVerificationCodes;

use App\Enum\LpaSource;
use App\Enum\LpaStatus;
use App\Enum\LpaType;
use App\Enum\VerificationCodeExpiryReason;
use DateTimeInterface;
use JsonSerializable;

class CodeUsable implements JsonSerializable
{
    public function __construct(
        public readonly string $donorName,
        public readonly LpaType $lpaType,
        public readonly LpaStatus $lpaStatus,
        public readonly LpaSource $lpaSource,
        public readonly ?DateTimeInterface $expiresAt = null,
        public readonly ?VerificationCodeExpiryReason $expiryReason = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'donorName' => $this->donorName,
            'type'      => $this->lpaType,
            'status'    => $this->lpaStatus,
            'source'    => $this->lpaSource,
        ];

        if ($this->expiresAt !== null) {
            $data['expiresAt'] = $this->expiresAt->format(DateTimeInterface::ATOM);
        }

        return $data;
    }
}
