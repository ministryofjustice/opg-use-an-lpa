<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CompleteDeleteAccountHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class CompleteDeleteAccountHandler implements RequestHandlerInterface
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
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $accountId = $request->getAttribute('account-id');

        if (!isset($accountId)) {
            throw new BadRequestException('Account Id must be provided for account deletion');
        }

        $user = $this->userService->deleteUserAccount($accountId);

        return new JsonResponse($user);
    }
}
