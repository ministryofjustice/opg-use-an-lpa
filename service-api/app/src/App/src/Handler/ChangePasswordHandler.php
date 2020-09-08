<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class ChangePasswordHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class ChangePasswordHandler implements RequestHandlerInterface
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

        if (!isset($requestData['password'])) {
            throw new BadRequestException('Current password must be provided');
        }

        if (!isset($requestData['new-password'])) {
            throw new BadRequestException('Replacement password must be provided');
        }

        $this->userService->completeChangePassword(
            $requestData['user-id'],
            new HiddenString($requestData['password']),
            new HiddenString($requestData['new-password'])
        );

        return new JsonResponse([]);
    }
}
