<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Class HomePageHandler
 * @package Viewer\Handler
 */
class HomePageHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($request->getMethod() == 'POST') {
            return $this->redirectToRoute('enter-code');
        }

        return new HtmlResponse($this->renderer->render('app::home-page'));
    }
}
