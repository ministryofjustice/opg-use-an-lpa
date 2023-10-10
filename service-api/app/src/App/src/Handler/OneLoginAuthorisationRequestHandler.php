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
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (empty($params['ui_locale'])) {
            throw new BadRequestException('Ui locale must be provided');
        }

        $ui_locale = strtolower($params['ui_locale']);
        if ($ui_locale !== 'en' and $ui_locale !== 'cy') {
            throw new BadRequestException('ui_locale is not set to en or cy');
        }

        $authorisationUri = $this->authorisationRequestService->createAuthorisationRequest($params['ui_locale']);

        return new JsonResponse($authorisationUri);
    }
}
