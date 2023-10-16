<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Service\OneLogin\OneLoginService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthoriseOneLoginHandler extends AbstractHandler
{
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private OneLoginService $authoriseOneLogin,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        //TODO: PLUG IN EN / CY from uri?
//        $result = $this->authoriseOneLogin->authorise('en');

        //TODO Store nonce and state values in session

        return new HtmlResponse($this->renderer->render('actor::one-login'));
    }
}
