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
 * Class CanResetEmailHandler
 * @package App\Handler
 * @codeCoverageIgnore
 */
class CanResetEmailHandler implements RequestHandlerInterface
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
        $requestData = $request->getQueryParams();

        if (empty($requestData['token'])) {
            throw new BadRequestException('Email reset token must be provided');
        }

        $userId = $this->userService->canResetEmail($requestData['token']);

        return new JsonResponse(['Id' => $userId]);
    }
}
