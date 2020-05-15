<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\ChangeEmail;
use Actor\Form\Login;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

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

    /** @var EmailClient */
    private $emailClient;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * RequestChangeEmailHandler constructor.
     *
     * @codeCoverageIgnore
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param AuthenticationInterface $authenticator
     * @param ServerUrlHelper $serverUrlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        EmailClient $emailClient,
        AuthenticationInterface $authenticator,
        ServerUrlHelper $serverUrlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
        $this->serverUrlHelper = $serverUrlHelper;

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
                $password = $formData['current_password'];

                try {
                    $data = $this->userService->requestChangeEmail($user->getIdentity(), $newEmail, $password);

                    $verifyNewEmailPath = $this->urlHelper->generate('verify-new-email', [
                        'token' => $data['EmailResetToken'],
                    ]);

                    $verifyNewEmailUrl = $this->serverUrlHelper->generate($verifyNewEmailPath);

                    //$this->emailClient->sendRequestChangeEmailToCurrentEmail($data['Email'], $data['NewEmail']);

                    $this->emailClient->sendRequestChangeEmailToNewEmail($data['NewEmail'], $verifyNewEmailUrl);

                    // log the user out before redirecting them to the login page
                    $session = $this->getSession($request, 'session');
                    $session->unset(UserInterface::class);
                    $session->regenerate();

                    return $this->redirectToRoute('login');
                } catch (ApiException $ex) {
                    //TODO: Use switch case if more cases to handle

                    if ($ex->getCode() === StatusCodeInterface::STATUS_FORBIDDEN) {
                        $form->addErrorMessage(ChangeEmail::INVALID_PASSWORD, 'current_password');
                    } elseif ($ex->getCode() === StatusCodeInterface::STATUS_CONFLICT) {
                        if ($user->getDetails()['Email'] === $newEmail) {
                            $form->addErrorMessage(ChangeEmail::NEW_EMAIL_NOT_DIFFERENT, 'new_email_address');
                        } else {
                            $form->addErrorMessage(ChangeEmail::NEW_EMAIL_CONFLICT, 'new_email_address');
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
