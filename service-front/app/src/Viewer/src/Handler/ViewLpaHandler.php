<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use DateInterval;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ViewLpaHandler extends AbstractHandler
{
    use SessionTrait;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private LpaService $lpaService,
        private FeatureEnabled $featureEnabled,
    )
    {
        parent::__construct($renderer, $urlHelper);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code         = $this->getSession($request, 'session')->get('code');
        $surname      = $this->getSession($request, 'session')->get('surname');
        $organisation = $this->getSession($request, 'session')->get('organisation');

        if (!isset($code)) {
            throw new SessionTimeoutException();
        }

        $lpaData = $this->lpaService->getLpaByCode($code, $surname, $organisation);

        $renderData = [
            'lpa' => $lpaData->lpa,
        ];

        // necessary for development. Do not uncomment for live environments.
        //$lpaData->expires = (new DateTimeImmutable('+60 days'))->format(\DateTimeInterface::ATOM);

        if (($this->featureEnabled)('instructions_and_preferences') && $lpaData->offsetExists('iap')) {
            // TODO UML-xxxx This date logic needs removing 30 days after 4th July (or whenever we go live, whichever is later)
            $codeCreated = (new DateTimeImmutable($lpaData->expires))->sub(new DateInterval('P30D'));

            if ($codeCreated > new DateTimeImmutable('2023-07-04T23:59:59+01:00')) {
                $renderData['iap_images'] = $lpaData->iap; // TODO UML-xxxx this is the only bit that should be kept
            }
        }

        return new HtmlResponse($this->renderer->render('viewer::view-lpa', $renderData));
    }
}
