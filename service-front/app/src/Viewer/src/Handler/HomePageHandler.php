<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HomePageHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($request->getMethod() == 'POST') {
            return $this->redirectToRoute('enter-code');
        }

        return new HtmlResponse($this->renderer->render('app::home-page'));
    }
}
