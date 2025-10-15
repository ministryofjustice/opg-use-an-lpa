<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Service\Lpa\Response\PaperVerificationCode;

enum PaperVerificationCodeStatus
{
    case OK;
    case NOT_FOUND;
    case EXPIRED;
    case CANCELLED;
}
