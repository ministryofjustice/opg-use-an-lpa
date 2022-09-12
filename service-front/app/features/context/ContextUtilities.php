<?php

declare(strict_types=1);

namespace BehatTest\Context;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ContextUtilities
{
    public static function newResponse(int $status, string $body = '', string $reason = ''): ResponseInterface
    {
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $file = $caller['file'];
        $line = $caller['line'];

        return new Response(
            status:  $status,
            headers: [],
            body:    $body,
            reason:  $reason . ' file: ' . $file . ' lineNumber: ' . $line,
        );
    }
}