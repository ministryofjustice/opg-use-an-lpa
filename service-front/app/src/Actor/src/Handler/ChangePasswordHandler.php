<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordChange;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\User\UserService;
use Common\Service\Email\EmailClient;
use Fig\Http\Message\StatusCodeInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class ChangePasswordHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ChangePasswordHandler extends AbstractHandler implements CsrfGuardAware, UserAware
{
    use CsrfGuard;
    use User;

    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * PasswordResetPageHandler constructor.
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
        $form = new PasswordChange($this->getCsrfGuard($request));

        $user = $this->getUser($request);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $this->userService->changePassword($user->getIdentity(), new HiddenString($data['current_password']), new HiddenString($data['new_password']));

                    $this->emailClient->sendPasswordChangedEmail($user->getDetail('email'));

                    return $this->redirectToRoute('your-details');
                } catch (ApiException $e) {
                    if ($e->getCode() === StatusCodeInterface::STATUS_FORBIDDEN) {
                        $form->addErrorMessage(PasswordChange::INVALID_PASSWORD, 'current_password');
                    }
                }
            }
        }

        return new HtmlResponse($this->renderer->render('actor::password-change', [
            'user' => $user,
            'form' => $form->prepare()
        ]));
    }
}
