<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class CompleteChangeEmailHandler implements RequestHandlerInterface
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestData = $request->getParsedBody();

        if (empty($requestData['reset_token'])) {
            throw new BadRequestException('Email reset token must be provided');
        }

        // also removes expiry token and new email field
        $this->userService->completeChangeEmail($requestData['reset_token']);

        return new JsonResponse([]);
    }
}
