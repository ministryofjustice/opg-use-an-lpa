<?php

declare(strict_types=1);

namespace Common\Middleware\ErrorHandling;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GoneHandler implements MiddlewareInterface
{
    /**
     * @var string[] List of URIs that are gone.
     */
    private array $goneUris = [
        '/reset-password',
        '/verify-new-email',
    ];

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private TemplateRendererInterface $renderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getUri()->getPath(), $this->goneUris)) {
            return $this->generateTemplatedResponse($this->renderer);
        }

        return $handler->handle($request);
    }

    private function generateTemplatedResponse(TemplateRendererInterface $renderer): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()->withStatus(StatusCodeInterface::STATUS_GONE);
        $response->getBody()->write($renderer->render('error::410'));

        return $response;
    }
}
