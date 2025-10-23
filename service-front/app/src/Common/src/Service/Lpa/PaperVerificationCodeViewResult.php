<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\CombinedLpa;

class PaperVerificationCodeViewResult
{
    public function __construct(
        public readonly PaperVerificationCodeStatus $status,
        public readonly ?CombinedLpa $data = null,
    ) {
    }
}
