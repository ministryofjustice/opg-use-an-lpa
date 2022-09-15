<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AuthHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class AuthHandler implements RequestHandlerInterface
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (empty($params['email']) || empty($params['password'])) {
            throw new BadRequestException('Email address and password must be provided');
        }

        $password = new HiddenString($params['password']);
        
        $user = $this->userService->authenticate($params['email'], $password);

        return new JsonResponse($user);
    }
}

