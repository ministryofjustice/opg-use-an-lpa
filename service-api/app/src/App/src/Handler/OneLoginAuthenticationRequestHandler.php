<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Authentication\OneLoginService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class OneLoginAuthenticationRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private OneLoginService $authenticationRequestService,
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

        if (empty($requestData['redirect_url'])) {
            throw new BadRequestException('Redirect URL must be provided');
        }

        if (empty($requestData['ui_locale'])) {
            throw new BadRequestException('Ui locale must be provided');
        }

        $redirect_url = $requestData['redirect_url'];
        $ui_locale    = strtolower($requestData['ui_locale']);
        if ($ui_locale !== 'en' and $ui_locale !== 'cy') {
            throw new BadRequestException('ui_locale is not set to en or cy');
        }

        $authRequest = $this->authenticationRequestService->createAuthenticationRequest($ui_locale, $redirect_url);

        return new JsonResponse($authRequest);
    }
}
