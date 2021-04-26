<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use Psr\Log\LoggerInterface;

class IsValidLpa
{
    private const LPA_REGISTERED = 'registered';
    private const LPA_CANCELLED = 'cancelled';

    private LoggerInterface $logger;
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Given a LPA, this returns a true flag if the status of lpa is Registered or Cancelled.
     *
     * This function is used by codes to check the validity of a LPA and its details to be displayed to user.
     *
     *
     * @param array $lpa An LPA data structure
     * @param string $actorId The actors Database ID or Sirius UId to search for within the $lpa data structure
     *
     * @return ?bool True if status is Registered or Cancelled
     */
    public function __invoke(array $lpa): ?bool
    {
        if (!(strtolower($lpa['status']) === self::LPA_REGISTERED || strtolower($lpa['status']) === self::LPA_CANCELLED)) {

            $this->logger->notice(
                'LPA with id {lpaUid} has an invalid status of {status}',
                [
                    'status' => $lpa['status'],
                    'lpaUid' => $lpa['uId']
                ]
            );
            return false;
        }
        return true;
    }
}
