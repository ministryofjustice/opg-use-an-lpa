<?php

declare(strict_types=1);

namespace App\Service\Lpa\IsValid;

use Psr\Log\LoggerInterface;

class IsValidLpa implements LpaValidationInterface
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
     * @param object $lpa An LPA data structure
     *
     * @return bool True if status is Registered or Cancelled
     */
    public function validate(array|object|null $lpa): bool
    {
        if (
            !(strtolower($lpa['status']) === LpaStatus::REGISTERED->value ||
                strtolower($lpa['status']) === LpaStatus::CANCELLED->value )
        ) {
            $this->logger->notice(
                'LPA with id {lpaUid} has an invalid status of {status}',
                [
                    'status' => $lpa->status,
                    'lpaUid' => $lpa->uId,
                ]
            );

            return false;
        }

        return true;
    }
}
