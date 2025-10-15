<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\Response\PaperVerificationCode;

class ParsePaperVerificationCode
{
    /**
     * @param array{donorName: string, type: string} $data
     */
    public function __invoke(array $data): PaperVerificationCode
    {
        return new PaperVerificationCode(
            donorName: $data['donorName'],
            lpaType: $data['type'],
        );
    }
}
