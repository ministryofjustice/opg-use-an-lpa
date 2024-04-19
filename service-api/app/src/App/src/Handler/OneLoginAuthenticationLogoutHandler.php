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
class OneLoginAuthenticationLogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private OneLoginService $logoutHandlingService,
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

        if (empty($requestData['user'])) {
            throw new BadRequestException('User must be provided');
        }

        if (empty($requestData['user']['IdToken'])) {
            throw new BadRequestException('User does not contain an OIDC token value');
        }

        return new JsonResponse(
            [
                'redirect_uri' => $this->logoutHandlingService->createLogoutUrl($requestData['user']['IdToken']),
            ],
        );
    }
}
