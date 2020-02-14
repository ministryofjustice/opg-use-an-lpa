<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class CanPasswordResetHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class CanPasswordResetHandler implements RequestHandlerInterface
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
        $requestData = $request->getQueryParams();

        if (!isset($requestData['token'])) {
            throw new BadRequestException('Password reset token must be provided');
        }

        $userId = $this->userService->canResetPassword($requestData['token']);

        return new JsonResponse(['Id' => $userId]);
    }
}
