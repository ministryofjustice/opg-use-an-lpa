<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\SessionAware;
use Common\Handler\Traits\Session;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class ViewLpaHandler extends AbstractHandler implements SessionAware
{
    use Session;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private LpaService $lpaService,
        private FeatureEnabled $featureEnabled,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code         = $this->getSession($request, 'session')?->get('code');
        $surname      = $this->getSession($request, 'session')?->get('surname');
        $organisation = $this->getSession($request, 'session')?->get('organisation');

        if (!isset($code)) {
            throw new SessionTimeoutException();
        }

        $lpaData = $this->lpaService->getLpaByCode($code, $surname, $organisation);

        $renderData = [
            'lpa' => $lpaData->lpa,
        ];

        if ($lpaData->offsetExists('iap')) {
            $this->logger->info(
                'Instructions and preferences images found for lpa {lpa_id} with state {state}',
                [
                    'lpa_id' => $lpaData->iap->uId,
                    'state'  => $lpaData->iap->status,
                ]
            );

            $renderData['iap_images'] = $lpaData->iap;
        }

        $templateName = 'viewer::view-lpa';
        if (($this->featureEnabled)('support_datastore_lpas')) {
            $templateName = 'viewer::view-lpa-combined-lpa';
        }

        return new HtmlResponse($this->renderer->render($templateName, $renderData));
    }
}
