<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class LpaHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        //  TODO - Full functionality to be completed later - for now just echo the code
        $shareCode = $request->getAttribute('shareCode');

        return new JsonResponse([
            'shareCode' => $shareCode
        ]);
    }
}
