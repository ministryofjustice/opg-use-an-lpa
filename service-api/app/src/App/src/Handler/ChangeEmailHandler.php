<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ChangeEmailHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class ChangeEmailHandler implements RequestHandlerInterface
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
        $requestData = $request->getParsedBody();

        if (!isset($requestData['user-id'])) {
            throw new BadRequestException('User Id must be provided');
        }

        if (!isset($requestData['new-email'])) {
            throw new BadRequestException('New email address must be provided');
        }

        if (!isset($requestData['password'])) {
            throw new BadRequestException('Current password must be provided');
        }

        $this->userService->requestChangeEmail($requestData['user-id'], $requestData['new-email'], $requestData['password']);
    }
}
