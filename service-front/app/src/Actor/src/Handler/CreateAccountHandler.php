<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateAccount;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;

/**
 * Class CreateAccountHandler
 * @package Viewer\Handler
 */
class CreateAccountHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new CreateAccount($guard);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                //  TODO - For now just redirect to create account page

                return $this->redirectToRoute('create-account');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account',[
            'form' => $form
        ]));
    }
}
