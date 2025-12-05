<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\Entity\Lpa;
use App\Service\Lpa\ResolveActor\LpaActor;
use App\Service\Lpa\SiriusLpa;
use DateTimeInterface;
use JsonSerializable;
use Spatie\Cloneable\Cloneable;

readonly class UserLpaActorToken implements JsonSerializable
{
    use Cloneable;

    public ?DateTimeInterface $activationKeyDueDate;
    public ?LpaActor $actor;
    public ?bool $hasPaperVerificationCode;

    public function __construct(
        public string $userLpaActorToken,
        public DateTimeInterface $lookupDateTime,
        public Lpa|SiriusLpa $lpa,
    ) {
        $this->activationKeyDueDate     = null;
        $this->actor                    = null;
        $this->hasPaperVerificationCode = null;
    }

    public function withActivationKeyDueDate(DateTimeInterface $activationKeyDueDate): self
    {
        return $this->with(activationKeyDueDate: $activationKeyDueDate);
    }

    public function withActor(LpaActor $actor): self
    {
        return $this->with(actor: $actor);
    }

    public function withHasPaperVerificationCode(bool $hasPaperVerificationCode): self
    {
        return $this->with(hasPaperVerificationCode: $hasPaperVerificationCode);
    }

    public function jsonSerialize(): array
    {
        $data = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
            'date'                 => $this->lookupDateTime->format(DateTimeInterface::ATOM),
            'lpa'                  => $this->lpa,
        ];

        if (isset($this->activationKeyDueDate)) {
            $data['activationKeyDueDate'] = $this->activationKeyDueDate->format(DateTimeInterface::ATOM);
        }

        if (isset($this->actor)) {
            $data['actor'] = $this->actor;
        }

        if (isset($this->hasPaperVerificationCode)) {
            $data['hasPaperVerificationCode'] = $this->hasPaperVerificationCode;
        }

        return $data;
    }
}
