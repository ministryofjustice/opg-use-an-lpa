<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\ConfirmEmail;
use Actor\Form\CreateAccount;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
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
class CreateAccountHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param EmailClient $emailClient
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        EmailClient $emailClient)
    {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
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
        $form = new CreateAccount($guard);

        if ($request->getMethod() === 'POST') {
            //  Check to see if this a post to register an account or to resend the activation token
            $requestData = $request->getParsedBody();

            if (array_key_exists('password', $requestData) && array_key_exists('password_confirm', $requestData)) {
                //  Request to create an account
                $form->setData($requestData);

                if ($form->isValid()) {
                    $formData = $form->getData();

                    $emailAddress = $formData['email'];
                    $password = $formData['password'];

                    try {
                        $userData = $this->userService->create($emailAddress, $password);

                        $this->sendActivationEmail($request, $emailAddress, $userData['ActivationToken']);
                    } catch (ApiException $ex) {
                        if ($ex->getCode() == StatusCodeInterface::STATUS_CONFLICT) {
                            $this->emailClient->sendAlreadyRegisteredEmail($emailAddress);
                        } else {
                            throw $ex;
                        }
                    }

                    return $this->returnConfirmationScreenResponse($request, $emailAddress);
                }
            } else {
                //  Request to resend the activation email - swap in the correct form
                $confirmEmailForm = new ConfirmEmail($guard);

                $confirmEmailForm->setData($requestData);

                if ($confirmEmailForm->isValid()) {
                    $confirmEmailFormData = $confirmEmailForm->getData();

                    $emailAddress = $confirmEmailFormData['email'];

                    try {
                        $userData = $this->userService->getByEmail($emailAddress);

                        //  Check to see if the user has activated their account by looking for an activation token
                        if (isset($userData['ActivationToken'])) {
                            $this->sendActivationEmail($request, $emailAddress, $userData['ActivationToken']);

                            return $this->returnConfirmationScreenResponse($request, $emailAddress);
                        }
                    } catch (ApiException $ignore) {
                        //  Ignore any API exception (e.g. user not found) and let the redirect below manage the request
                    }
                }

                //  If we have got to this point then something has gone wrong so just do a clean redirect to the create account screen
                return $this->redirectToRoute('create-account');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account', [
            'form' => $form,
        ]));
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $emailAddress
     * @return HtmlResponse
     */
    private function returnConfirmationScreenResponse(ServerRequestInterface $request, string $emailAddress)
    {
        //  Account created successfully so set up the new form and go to the confirmation screen
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new ConfirmEmail($guard);

        //  Populate the email address in the form
        $form->populateValues([
            'email'         => $emailAddress,
            'email_confirm' => $emailAddress,
        ]);

        return new HtmlResponse($this->renderer->render('actor::create-account-success', [
            'form'         => $form,
            'emailAddress' => $emailAddress,
        ]));
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $emailAddress
     * @param string $activationToken
     */
    private function sendActivationEmail(ServerRequestInterface $request, string $emailAddress, string $activationToken) : void
    {
        $host = sprintf('%s://%s', $request->getUri()->getScheme(), $request->getUri()->getAuthority());

        $activateAccountUrl = $host . $this->urlHelper->generate('activate-account', [
            'token' => $activationToken,
        ]);

        $this->emailClient->sendAccountActivationEmail($emailAddress, $activateAccountUrl);
    }
}
