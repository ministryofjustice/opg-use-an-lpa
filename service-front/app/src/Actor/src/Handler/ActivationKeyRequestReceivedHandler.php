<?php

namespace Actor\Handler;

use Carbon\Carbon;
use Common\Handler\AbstractHandler;
use Common\Service\Email\EmailClient;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\User\UserService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\Element\DateTime;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ActivationKeyRequestReceivedHandler extends AbstractHandler
{
    private LocalisedDate $localisedDate;

    /**
     * ActivateAccountHandler constructor.
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper                 $urlHelper
     * @param UserService               $userService
     * @param EmailClient               $emailClient
     * @param ServerUrlHelper           $serverUrlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        EmailClient $emailClient,
        ServerUrlHelper $serverUrlHelper,
        LocalisedDate $localisedDate
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->emailClient = $emailClient;
        $this->serverUrlHelper = $serverUrlHelper;
        $this->localisedDate = $localisedDate;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $date = (new Carbon())->addWeeks(2);

        ($this->localisedDate)($date);

        return new HtmlResponse($this->renderer->render('actor::activation-key-request-received', [
            'date' => $date
        ]));
    }
}
