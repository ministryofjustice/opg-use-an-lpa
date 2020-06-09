<?php

declare(strict_types=1);

namespace App\Service\ActorCodes;

use App\Exception\ActorCodeMarkAsUsedException;
use App\Exception\ActorCodeValidationException;

interface CodeValidationStrategyInterface
{
    /**
     * Checks that the given combination of parameters is a valid one-time-use actor code and returns the
     * actors uId from the Sirius record if that is the case.
     *
     * @param string $code
     * @param string $uid
     * @param string $dob
     * @return string The actor Uid from Sirius
     * @throws ActorCodeValidationException Thrown when the validation of a set of details fails
     */
    public function validateCode(string $code, string $uid, string $dob): string;

    /**
     * Marks a one-time-use actor code as used and returns the id of the UserLpaActorMap linking record.
     *
     * @param string $code
     * @throws ActorCodeMarkAsUsedException Thrown when the act of marking a code as used fails
     */
    public function flagCodeAsUsed(string $code);
}
