<?php

declare(strict_types=1);

namespace Viewer\Handler;


use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewerSessionCheckHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // $data = $this->session->

        return new JsonResponse(["test"], 201);
    }
}
