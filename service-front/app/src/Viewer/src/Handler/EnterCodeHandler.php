<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\ShareCode;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class EnterCodeHandler
 * @package Viewer\Handler
 */
class EnterCodeHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;
    use SessionTrait;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $this->getSession($request, 'session');

        $form = new ShareCode($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $lpaCode = $form->getData()['lpa_code'];

                $session->set('code', $lpaCode);
                $session->set('surname', $form->getData()['donor_surname']);

                return $this->redirectToRoute('check-code');
            }
        }

        return new HtmlResponse($this->renderer->render('viewer::enter-code', [
            'form' => $form
        ]));
    }
}
