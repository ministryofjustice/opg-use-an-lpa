<?php

declare(strict_types=1);

namespace Actor\Handler;


use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionPersistenceInterface AS Session;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ActorSessionCheckHandler extends AbstractHandler
{
    private Session $session;

    /**
     * ActorSessionCheckHandler constructor.
     * @param Session $session
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Session $session,
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->session = $session;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // $data = $this->session->

        return new JsonResponse(["test"], 201);
    }
}
