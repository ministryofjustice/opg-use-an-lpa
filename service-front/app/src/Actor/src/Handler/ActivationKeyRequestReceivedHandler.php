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
     * @param LocalisedDate             $localisedDate
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LocalisedDate $localisedDate
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->localisedDate = $localisedDate;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $date = (new Carbon())->addWeeks(6);

        ($this->localisedDate)($date);

        return new HtmlResponse($this->renderer->render('actor::activation-key-request-received', [
            'date' => $date
        ]));
    }
}
