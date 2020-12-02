<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Login;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Common\Exception\RateLimitExceededException;
use Common\Middleware\Security\UserIdentificationMiddleware;
use Common\Service\Security\RateLimitService;

/**
 * Class CreateAccountHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class LoginPageHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use User;
    use CsrfGuard;
    use Logger;

    /** @var RateLimitService */
    private $rateLimitService;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param AuthenticationInterface $authenticator
     * @param LoggerInterface $logger
     * @param RateLimitService $rateLimitService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
        RateLimitService $rateLimitService
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception|\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Login($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if (!$form->isValid()) {
                $errors = $form->getMessages();

                $this->getLogger()->notice('Login form validation failed.', $errors);

                return new HtmlResponse($this->renderer->render('actor::login', [
                    'form' => $form
                ]));
            }

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
                       'email' => $emailAddress
                   ]);
               }
           }
        }

        // user is already logged in. check done *after* POST method above due to the way
        // the auth middleware functions
        if ($this->getUser($request) !== null) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        $this->rateLimitService->limit($request->getAttribute(UserIdentificationMiddleware::IDENTIFY_ATTRIBUTE));

        return new HtmlResponse($this->renderer->render('actor::login', [
            'form' => $form
        ]));
    }
}
