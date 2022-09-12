<?php

declare(strict_types=1);

namespace BehatTest\Context;

use BehatTest\Context\UI\LpaContext;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ContextUtilities
{
    /**
     * @param int    $status The status code of the request
     * @param string $body the body of the request
     * @param string $reason this hijacks the reason phrase for debug purposes so that we can input the api call that
     *                       the response is for. @see LpaContext::iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
     *
     * @return ResponseInterface
     */
    public static function newResponse(int $status, string $body, string $reason = ''): ResponseInterface
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
