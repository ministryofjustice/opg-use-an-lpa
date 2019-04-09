<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class EnterCodeHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $s = $this->getSession($request,'session');

        $s->set('test', 'hello');

        if ($request->getMethod() == 'POST') {
            //  TODO - Some processing here...
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::enter-code'));
    }
}
