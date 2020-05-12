<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Login;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class CreateAccountHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
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
        AuthenticationInterface $authenticator
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Login($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                try {
                    $user = $this->getUser($request);

                    if (! is_null($user)) {
                        if (empty($user->getDetail('LastLogin'))) {
                            return $this->redirectToRoute('lpa.add');
                        } else {
                            return $this->redirectToRoute('lpa.dashboard');
                        }
                    }
                    // adding an element name allows the form to link the error message to a field. In this case we'll
                    // link to the email field to allow the user to correct their mistake.
                    $form->addErrorMessage(Login::INVALID_LOGIN, 'email');
                } catch (ApiException $e) {
                    //401 denotes in this case that we hve not activated,
                    // redirect to correct success page with correct data
                    if ($e->getCode() === StatusCodeInterface::STATUS_UNAUTHORIZED) {
                        $formValues = $form->getData();
                        $emailAddress = $formValues['email'];

                        return $this->redirectToRoute('create-account-success', [], [
                           'email' => $emailAddress,
                           'accountExists' => 'true'
                        ]);
                    }
                }
            }
        }

        // user is already logged in. check done *after* POST method above due to the way
        // the auth middleware functions
        if ($this->getUser($request) !== null) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        // display the notice message if the user has been redirected from the change email page
        $referer = $request->getHeader('referer')[0];
        if (!is_null($referer)) {
            if (strpos($referer, '/change-email') !== false) {
                $form->addNotice(Login::EMAIL_CHANGE, 'email');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::login', [
            'form' => $form
        ]));
    }
}
