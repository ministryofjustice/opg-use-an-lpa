<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Session;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class CreateAccountSuccessHandler extends AbstractHandler implements SessionAware
{
    use Session;

    public const SESSION_EMAIL_KEY = CreateAccountHandler::SESSION_EMAIL_KEY;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private UserService $userService,
        private ServerUrlHelper $serverUrlHelper,
        private NotifyService $notifyService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /*
         * IMPORTANT: - This handler is currently configured to receive a GET request ONLY
         *              This is only allowed because the current processing DOES NOT update
         *              the activation token that is set for the user.
         *              If this changes in future such that the token is regenerated then
         *              this handler must be changed to receive a POST request.
         */

        $params = $request->getQueryParams();

        // Retrieve email from session
        $session      = $this->getSession($request, 'session');
        $emailAddress = $session->get(self::SESSION_EMAIL_KEY);
        $resend       = (isset($params['resend']) && $params['resend'] === 'true');

        if (is_null($emailAddress)) {
            return $this->redirectToRoute('create-account');
        }

        if ($resend === true) {
            try {
                $userData = $this->userService->getByEmail($emailAddress);

                //  Check to see if the user has activated their account by looking for an activation token
                if (isset($userData['ActivationToken'])) {
                    $activateAccountPath = $this->urlHelper->generate('activate-account', [
                        'token' => $userData['ActivationToken'],
                    ]);

                    $activateAccountUrl = $this->serverUrlHelper->generate($activateAccountPath);

                    $this->notifyService->sendEmailToUser(
                        NotifyService::ACTIVATE_ACCOUNT_TEMPLATE,
                        $emailAddress,
                        activateAccountUrl: $activateAccountUrl
                    );

                    //  Redirect back to this page without the resend flag - do this to guard against repeated page refreshes
                    return $this->redirectToRoute('create-account-success');
                }
            } catch (ApiException) {
                //  Ignore any API exception (e.g. user not found) and let the redirect below manage the request
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account-success', [
            'emailAddress' => $emailAddress,
        ]));
    }
}
