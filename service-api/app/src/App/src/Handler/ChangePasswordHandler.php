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
 * Class ChangePasswordHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class ChangePasswordHandler implements RequestHandlerInterface
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

        if (empty($requestData['user-id'])) {
            throw new BadRequestException('User Id must be provided');
        }

        if (empty($requestData['password'])) {
            throw new BadRequestException('Current password must be provided');
        }

        if (empty($requestData['new-password'])) {
            throw new BadRequestException('Replacement password must be provided');
        }

        $this->userService->completeChangePassword(
            $requestData['user-id'],
            new HiddenString($requestData['password']),
            new HiddenString($requestData['new-password']),
        );

        return new JsonResponse([]);
    }
}
