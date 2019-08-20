<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordReset;
use Common\Handler\AbstractHandler;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class PasswordResetPageHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * CreateAccountHandler constructor.
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
        ServerUrlHelper $serverUrlHelper)
    {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new PasswordReset($guard);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                $resetToken = $this->userService->requestPasswordReset($data['email']);


                return new HtmlResponse($this->renderer->render('actor::password-reset-done',[
                    'email' => $data['email']
                ]));
            }
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset',[
            'form' => $form
        ]));
    }
}