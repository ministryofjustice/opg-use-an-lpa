<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateAccount;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Common\Service\Notify\NotifyService;

/**
 * Class CreateAccountHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CreateAccountHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /** @var UserService */
    private $userService;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /** @var NotifyService */
    private $notifyService;

    /**
     * CreateAccountHandler constructor.
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
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CreateAccount($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            //  Check to see if this a post to register an account or to resend the activation token
            $requestData = $request->getParsedBody();

            //  Request to create an account
            $form->setData($requestData);

            if ($form->isValid()) {
                $formData = $form->getData();

                $emailAddress = $formData['email'];

                $password = new HiddenString($formData['show_hide_password']);

                try {
                    $userData = $this->userService->create($emailAddress, $password);

                    //  Send account activation email to user
                    $activateAccountPath = $this->urlHelper->generate('activate-account', [
                        'token' => $userData['ActivationToken'],
                    ]);

                    $activateAccountUrl = $this->serverUrlHelper->generate($activateAccountPath);

                    $this->notifyService->sendEmailToUser(
                        NotifyService::ACTIVATE_ACCOUNT_TEMPLATE,
                        $emailAddress,
                        activateAccountUrl: $activateAccountUrl
                    );
                } catch (ApiException $ex) {
                    if ($ex->getCode() == StatusCodeInterface::STATUS_CONFLICT) {
                        $this->notifyService->sendEmailToUser(
                            NotifyService::ALREADY_REGISTERED_EMAIL_TEMPLATE,
                            $emailAddress
                        );
                    } else {
                        throw $ex;
                    }
                }

                //  Redirect to the success screen with the email address so that we can utilise the resend activation token functionality
                return $this->redirectToRoute('create-account-success', [], [
                    'email' => $emailAddress,
                ]);
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account', [
            'form' => $form,
        ]));
    }
}
