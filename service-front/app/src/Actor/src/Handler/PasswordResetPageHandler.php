<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordReset;
use Common\Handler\AbstractHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class PasswordResetPageHandler extends AbstractHandler
{
    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper)
    {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new PasswordReset($guard);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                // TODO actual email sending.

                return new HtmlResponse($this->renderer->render('actor::password-reset-done',[
                    'email' => $data['email']
                ]));
            }
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset',[
            'form' => $form
        ]));
    }
}