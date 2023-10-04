<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\BadRequestException;
use App\Service\Authentication\AuthenticationService;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AuthRedirectHandler
 *
 * @package App\Handler
 * @codeCoverageIgnore
 */
class AuthRedirectHandler implements RequestHandlerInterface
{
    public function __construct(
        private AuthenticationService $authenticationService,
    ) {
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (empty($params['ui_locale'])) {
            throw new BadRequestException('Ui locale must be provided');
        }

        $authorisationUri = $this->authenticationService->redirect($params['ui_locale']);

        return new JsonResponse($authorisationUri);
    }
}