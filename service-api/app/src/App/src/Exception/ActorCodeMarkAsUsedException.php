<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Thrown when an upstream service fails to mark an actor code as used.
 */
class ActorCodeMarkAsUsedException extends Exception
{
}
