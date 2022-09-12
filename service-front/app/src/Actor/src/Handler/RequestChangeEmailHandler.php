<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\ChangeEmail;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Session\EncryptedCookiePersistence;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Common\Service\Notify\NotifyService;

/**
 * Class RequestChangeEmailHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RequestChangeEmailHandler extends AbstractHandler implements CsrfGuardAware, UserAware, SessionAware
{
    use CsrfGuard;
    use User;
    use Session;

    /** @var UserService */
    private $userService;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /** @var NotifyService */
    private $notifyService;

    /**
     * RequestChangeEmailHandler constructor
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param AuthenticationInterface $authenticator
     * @param ServerUrlHelper $serverUrlHelper
     * @param NotifyService $notifyService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        AuthenticationInterface $authenticator,
        ServerUrlHelper $serverUrlHelper,
        NotifyService $notifyService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->serverUrlHelper = $serverUrlHelper;
        $this->notifyService = $notifyService;

        $this->setAuthenticator($authenticator);
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new ChangeEmail($this->getCsrfGuard($request));

        $user = $this->getUser($request);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $formData = $form->getData();

                $newEmail = $formData['new_email_address'];
                $password = new HiddenString($formData['current_password']);

                if ($newEmail === $user->getDetails()['Email']) {
                    $form->addErrorMessage(ChangeEmail::NEW_EMAIL_NOT_DIFFERENT);
                } else {
                    try {
                        $data = $this->userService->requestChangeEmail($user->getIdentity(), $newEmail, $password);

                        $verifyNewEmailPath = $this->urlHelper->generate('verify-new-email', [
                            'token' => $data['EmailResetToken'],
                        ]);

                        $verifyNewEmailUrl = $this->serverUrlHelper->generate($verifyNewEmailPath);

                        $this->notifyService->sendEmailToUser(
                            NotifyService::REQUEST_CHANGE_EMAIL_TO_CURRENT_EMAIL,
                            $data['Email'],
                            newEmailAddress:$data['NewEmail']
                        );

                        $this->notifyService->sendEmailToUser(
                            NotifyService::REQUEST_CHANGE_EMAIL_TO_NEW_EMAIL,
                            $data['NewEmail'],
                            completeEmailChangeUrl:$verifyNewEmailUrl
                        );

                        $session = $this->getSession($request, 'session');
                        $session->set(EncryptedCookiePersistence::SESSION_EXPIRED_KEY, true);
                        $session->regenerate();

                        return new HtmlResponse($this->renderer->render('actor::request-email-change-success', [
                            'user'     => $user,
                            'newEmail' => $newEmail
                        ]));
                    } catch (ApiException $ex) {
                        if ($ex->getCode() === StatusCodeInterface::STATUS_FORBIDDEN) {
                            $form->addErrorMessage(ChangeEmail::INVALID_PASSWORD);
                        } elseif ($ex->getCode() === StatusCodeInterface::STATUS_CONFLICT) {
                            // send email to the other user who has not completed their reset saying someone has tried
                            // to use their email

                            $this->notifyService->sendEmailToUser(
                                NotifyService::SOMEONE_TRIED_TO_USE_YOUR_EMAIL_IN_EMAIL_RESET_REQUEST_TEMPLATE,
                                $newEmail
                            );

                            return new HtmlResponse($this->renderer->render('actor::request-email-change-success', [
                                'user'     => $user,
                                'newEmail' => $newEmail
                            ]));
                        }
                    }
                }
            }
        }

        return new HtmlResponse($this->renderer->render('actor::change-email', [
            'form' => $form->prepare(),
            'user' => $user
        ]));
    }
}
