<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Service\Lpa\IsValid\isValidInterface;
use App\Service\Lpa\IsValid\LpaStatus;
use Psr\Log\LoggerInterface;

class IsValidLpa
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Given a LPA, this returns a true flag if the status of lpa is Registered or Cancelled.
     *
     * This function is used by codes to check the validity of a LPA and its details to be displayed to user.
     *
     * @param isValidInterface $lpa An LPA data structure
     *
     * @return bool True if status is Registered or Cancelled
     */
    public function __invoke(isValidInterface $lpa): bool
    {
        $status = $lpa->getStatus();
        $uid    = $lpa->getUid();

        if (
            !(strtolower($status) === LpaStatus::REGISTERED->value ||
                strtolower($status) === LpaStatus::CANCELLED->value )
        ) {
            $this->logger->notice(
                'LPA with id {lpaUid} has an invalid status of {status}',
                [
                    'status' => $status,
                    'lpaUid' => $uid,
                ]
            );

            return false;
        }

        return true;
    }
}
