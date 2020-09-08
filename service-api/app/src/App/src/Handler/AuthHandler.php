<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class AuthHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class AuthHandler implements RequestHandlerInterface
{
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (!isset($params['email']) || !isset($params['password'])) {
            throw new BadRequestException('Email address and password must be provided');
        }

        $user = $this->userService->authenticate($params['email'], new HiddenString($params['password']));

        return new JsonResponse($user);
    }
}
