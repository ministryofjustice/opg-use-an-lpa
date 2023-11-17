<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Thrown when a set of actor code credentials (incl. DoB and LPA uId) fail to validate.
 */
class ActorCodeValidationException extends Exception
{
}
