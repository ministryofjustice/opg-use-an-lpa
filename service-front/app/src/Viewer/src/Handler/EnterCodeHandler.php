<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\ShareCode;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Class EnterCodeHandler
 * @package Viewer\Handler
 */
class EnterCodeHandler extends AbstractHandler
{
    use CsrfGuard;
    use SessionTrait;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $session = $this->getSession($request, 'session');

        $form = new ShareCode($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $session->set('code', $form->getData()['lpa_code']);

                return $this->redirectToRoute('check-code');
            }
        }

        return new HtmlResponse($this->renderer->render('viewer::enter-code', [
            'form' => $form
        ]));
    }
}
