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
 * Class CompleteChangeEmailHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class CompleteChangeEmailHandler implements RequestHandlerInterface
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

        if (!isset($requestData['reset_token'])) {
            throw new BadRequestException('Email reset token must be provided');
        }

        // also removes expiry token and new email field
        $this->userService->completeChangeEmail($requestData['reset_token']);

        return new JsonResponse([]);
    }
}
