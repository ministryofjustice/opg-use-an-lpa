<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateAccount;
use Common\Handler\AbstractHandler;
use Common\Service\ApiClient\ApiException;
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
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                $emailAddress = $data['email'];
                $password = $data['password'];

                try {
                    $userData = $this->userService->create($emailAddress, $password);

                    $host = sprintf('%s://%s', $request->getUri()->getScheme(), $request->getUri()->getAuthority());

                    $activateAccountUrl = $host . $this->urlHelper->generate('activate-account', [
                        'token' => $userData['ActivationToken'],
                    ]);

                    $this->emailClient->sendAccountActivationEmail($emailAddress, $activateAccountUrl);
                } catch (ApiException $ex) {
                    if ($ex->getCode() == StatusCodeInterface::STATUS_CONFLICT) {
                        $this->emailClient->sendAlreadyRegisteredEmail($emailAddress);
                    } else {
                        throw $ex;
                    }
                }

                //  TODO - For now just redirect to create account page

                return $this->redirectToRoute('create-account');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account',[
            'form' => $form
        ]));
    }
}
