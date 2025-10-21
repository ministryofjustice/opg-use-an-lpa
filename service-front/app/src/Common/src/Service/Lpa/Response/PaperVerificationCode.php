<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response;

class PaperVerificationCode extends Response
{
    public function __construct(
        public readonly string $donorName,
        public readonly string $lpaType,
    ) {
    }
}
