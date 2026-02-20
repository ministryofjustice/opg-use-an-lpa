<?php

declare(strict_types=1);

namespace Common\Service\OneLogin;

use JsonSerializable;

final class AuthenticationData implements JsonSerializable
{
    private function __construct(
        public readonly ?string $state = null,
        private readonly ?string $nonce = null,
        public readonly ?array $customs = [],
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            $array['state'] ?? null,
            $array['nonce'] ?? null,
            $array['customs'] ?? null
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'state'   => $this->state,
                'nonce'   => $this->nonce,
                'customs' => $this->customs,
            ]
        );
    }
}
