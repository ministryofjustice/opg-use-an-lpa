<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordResetRequest;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Common\Service\Notify\NotifyService;

/**
 * Class PasswordResetRequestPageHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class PasswordResetRequestPageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /** @var UserService */
    private $userService;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /** @var NotifyService */
    private $notifyService;

    /**
     * PasswordResetRequestPageHandler constructor.
     *
     * @codeCoverageIgnore
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param ServerUrlHelper $serverUrlHelper
     * @param NotifyService $notifyService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        ServerUrlHelper $serverUrlHelper,
        NotifyService $notifyService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->serverUrlHelper = $serverUrlHelper;
        $this->notifyService = $notifyService;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new PasswordResetRequest($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $resetToken = $this->userService->requestPasswordReset($data['email']);

                    $passwordResetPath = $this->urlHelper->generate('password-reset-token', [
                        'token' => $resetToken,
                    ]);

                    $passwordResetUrl = $this->serverUrlHelper->generate($passwordResetPath);

                    if (!empty($data['forced'])) {
                        $this->notifyService->sendEmailToUser(
                            $data['email'],
                            $passwordResetUrl,
                            $emailTemplate = 'PasswordResetEmail',
                            null,
                            null,
                            null
                        );

                        //$this->emailClient->sendForcePasswordResetEmail($data['email'], $passwordResetUrl);
                    } else {
                        $this->notifyService->sendEmailToUser(
                            $data['email'],
                            $passwordResetUrl,
                            $emailTemplate = 'PasswordResetEmail',
                            null,
                            null,
                            null
                        );
                    }
                } catch (ApiException $ae) {
                    // the password reset request returned a 404 indicating the user did not exist
                    $this->notifyService->sendEmailToUser(
                        $data['email'],
                        null,
                        $emailTemplate = 'NoAccountExistsEmail',
                        null,
                        null,
                        null
                    );
                }

                return new HtmlResponse($this->renderer->render('actor::password-reset-request-done', [
                    'email' => strtolower($data['email'])
                ]));
            }
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset-request', [
            'form' => $form
        ]));
    }
}
