<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Authentication\OneLoginAuthorisationRequestService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @codeCoverageIgnore
 */
class OneLoginAuthorisationRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private OneLoginAuthorisationRequestService $authorisationRequestService,
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

        if (empty($requestData['ui_locale'])) {
            throw new BadRequestException('Ui locale must be provided');
        }

        if (empty($requestData['redirect_url'])) {
            throw new BadRequestException('Redirect URL must be provided');
        }

        $ui_locale = strtolower($requestData['ui_locale']);
        $redirect_url = $requestData['redirect_url'];

        if ($ui_locale !== 'en' and $ui_locale !== 'cy') {
            throw new BadRequestException('ui_locale is not set to en or cy');
        }

        return new JsonResponse($this->authorisationRequestService->createAuthorisationRequest($ui_locale, $redirect_url));
    }
}
