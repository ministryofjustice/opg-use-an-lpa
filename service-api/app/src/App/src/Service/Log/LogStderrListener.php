<?php

declare(strict_types=1);

namespace App\Service\Log;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LogStderrListener
{

    /**
     * Style and output errors to STDERR (For use with Docker)
     *
     * @codeCoverageIgnore
     *
     * @param Throwable $error
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response)
    {
        $output = "---------------------------------------------\n";
        $output .= $error->getMessage()."\n";
        $output .= 'On line '.$error->getLine().' in '.$error->getFile()."\n";
        $output .= $error->getTraceAsString()."\n";
        $output .= "---------------------------------------------\n";

        $fh = fopen('php://stderr','a');
        fwrite($fh,$output);
        fclose($fh);
    }
}
