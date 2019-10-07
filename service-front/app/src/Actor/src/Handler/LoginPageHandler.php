<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Login;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class CreateAccountHandler
 * @package Actor\Handler
 */
class LoginPageHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $form = new Login($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $user = $this->getUser($request);

                if ( ! is_null($user)) {
                    return $this->redirectToRoute('lpa.dashboard');
                }

                // adding an element name allows the form to link the error message to a field. In this case we'll
                // link to the email field to allow the user to correct their mistake.
                $form->addErrorMessage(Login::INVALID_LOGIN, 'email');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::login', [
            'form' => $form
        ]));
    }
}
