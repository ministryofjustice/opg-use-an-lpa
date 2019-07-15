<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\LpaAdd;
use Common\Handler\AbstractHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;

/**
 * Class LpaAddHandler
 * @package Actor\Handler
 */
class LpaAddHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new LpaAdd($guard);

        if ($request->getMethod() === 'POST') {

            //TODO

        }

        return new HtmlResponse($this->renderer->render('actor::lpa-add', [
            'form' => $form
        ]));
    }
}
