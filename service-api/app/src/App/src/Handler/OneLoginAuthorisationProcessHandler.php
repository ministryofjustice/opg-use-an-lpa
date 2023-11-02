<?php

declare(strict_types=1);

namespace App\Handler;

use DateTime;
use DateTimeInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class OneLoginAuthorisationProcessHandler implements RequestHandlerInterface
{
    public function __construct()
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getQueryParams();

        $user = [
            'Id'        => 'one-login-user',
            'Email'     => 'one-login-user@email.com',
            'LastLogin' => (new DateTime('now'))->format(DateTimeInterface::ATOM),
        ];

        return new JsonResponse($user);
    }
}
