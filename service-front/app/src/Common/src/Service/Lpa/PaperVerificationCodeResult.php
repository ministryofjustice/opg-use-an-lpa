<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Lpa\Response\PaperVerificationCode;

class PaperVerificationCodeResult
{
    public function __construct(
        public readonly PaperVerificationCodeStatus $status,
        public readonly ?PaperVerificationCode $data = null,
    ) {
    }
}
