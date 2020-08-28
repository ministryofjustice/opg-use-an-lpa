<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class CreateAccountSuccessHandler
 * @package Actor\Handler
 */
class CreateAccountSuccessHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * CreateAccountSuccessHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param EmailClient $emailClient
     * @param ServerUrlHelper $serverUrlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        EmailClient $emailClient,
        ServerUrlHelper $serverUrlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
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

        /** @var string $emailAddress */
        $emailAddress = $params['email'] ?? null;
        $resend = (isset($params['resend']) && $params['resend'] === 'true');

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

                    $this->emailClient->sendAccountActivationEmail($emailAddress, $activateAccountUrl);

                    //  Redirect back to this page without the resend flag - do this to guard against repeated page refreshes
                    return $this->redirectToRoute('create-account-success', [], [
                        'email' => $emailAddress,
                    ]);
                }
            } catch (ApiException $ignore) {
                //  Ignore any API exception (e.g. user not found) and let the redirect below manage the request
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account-success', [
            'emailAddress' => $emailAddress
        ]));
    }
}
