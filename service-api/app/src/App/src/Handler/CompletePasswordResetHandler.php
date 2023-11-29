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
 * @codeCoverageIgnore
 */
class CompletePasswordResetHandler implements RequestHandlerInterface
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

        if (empty($requestData['token'])) {
            throw new BadRequestException('Password reset token must be provided');
        }

        if (empty($requestData['password'])) {
            throw new BadRequestException('Replacement password must be provided');
        }

        $this->userService->completePasswordReset(
            $requestData['token'],
            new HiddenString($requestData['password']),
        );

        return new JsonResponse([]);
    }
}
