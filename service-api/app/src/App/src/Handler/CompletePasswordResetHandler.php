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
 * Class CompletePasswordResetHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class CompletePasswordResetHandler implements RequestHandlerInterface
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

        if (!isset($requestData['token'])) {
            throw new BadRequestException('Password reset token must be provided');
        }

        if (!isset($requestData['password'])) {
            throw new BadRequestException('Replacement password must be provided');
        }

        $this->userService->completePasswordReset($requestData['token'], new HiddenString($requestData['password']));

        return new JsonResponse([]);
    }
}
