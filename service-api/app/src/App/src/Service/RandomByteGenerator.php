<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\RandomException;
use Throwable;

use function random_bytes;

/**
 * @internal
 *
 * @todo This is redundant under PHP 8.3 as random_bytes throws a named exception.
 */
class RandomByteGenerator
{
    /**
     * @param int $count The number of bytes required
     * @return string The requested random bytes
     * @throws RandomException An error was encountered whilst using PHPs random_bytes function
     */
    public function __invoke(int $count): string
    {
        try {
            return random_bytes($count);

        // @codeCoverageIgnoreStart
        } catch (Throwable $t) {
            throw new RandomException($t->getMessage(), (int) $t->getCode(), $t);
        }
        // @codeCoverageIgnoreEnd
    }
}
