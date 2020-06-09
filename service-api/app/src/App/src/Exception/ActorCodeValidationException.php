<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Class ActorCodeValidationException
 *
 * Thrown when a set of actor code credentials (incl. DoB and LPA uId) fail to validate.
 *
 * @package App\Exception
 */
class ActorCodeValidationException extends \Exception
{
}
