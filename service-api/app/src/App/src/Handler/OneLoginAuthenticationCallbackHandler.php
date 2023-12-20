<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Authentication\OneLoginService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class OneLoginAuthenticationCallbackHandler implements RequestHandlerInterface
{
    public function __construct(
        private OneLoginService $callbackHandlingService,
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

        if (empty($requestData['code'])) {
            throw new BadRequestException('Code must be provided');
        }

        if (empty($requestData['state'])) {
            throw new BadRequestException('State must be provided');
        }

        if (empty($requestData['auth_session'])) {
            throw new BadRequestException('An auth session must be provided');
        }

        if (empty($requestData['auth_session']['state'])) {
            throw new BadRequestException('The auth session must contain a state');
        }

        if (empty($requestData['auth_session']['nonce'])) {
            throw new BadRequestException('The auth session must contain a nonce');
        }

        if (empty($requestData['auth_session']['customs']['redirect_uri'])) {
            throw new BadRequestException('The auth session must contain a redirect_uri');
        }

        $user = $this->callbackHandlingService->handleCallback(
            $requestData['code'],
            $requestData['state'],
            $requestData['auth_session']
        );

        return new JsonResponse($user);
    }
}
