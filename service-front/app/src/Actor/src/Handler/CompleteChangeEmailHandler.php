<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CompleteChangeEmailHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /**
     * CompleteChangeEmailHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $verificationToken = $request->getAttribute('token');

        $verified = $this->userService->completeChangeEmail($verificationToken);

        if (!$verified) {
            //TODO: Create token not found page
            return new HtmlResponse($this->renderer->render('actor::activate-account-not-found'));
        }

        //TODO: Create email changed page
        return new HtmlResponse($this->renderer->render('actor::activate-account'));
    }
}
