<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Login;
use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class CreateAccountHandler
 * @package Actor\Handler
 */
class LoginPageHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new Login($guard);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                // TODO do actual login with something like Zend_Authentication
                $loggedIn = false;
                if ($data['email'] === 'test@example.com') {
                    $loggedIn = true;

                    //  TODO for now just redirect to home page
                    return $this->redirectToRoute('home');
                }

                if ( ! $loggedIn) {
                    // adding an element name allows the form to link the error message to a field. In this case we'll
                    // link to the email field to allow the user to correct their mistake.
                    $form->addErrorMessage(Login::INVALID_LOGIN, 'email');
                }
            }
        }

        return new HtmlResponse($this->renderer->render('actor::login',[
            'form' => $form
        ]));
    }
}
