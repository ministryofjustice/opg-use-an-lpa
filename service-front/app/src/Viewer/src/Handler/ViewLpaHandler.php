<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
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

    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper, private LpaService $lpaService)
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

        $lpa = $this->lpaService->getLpaByCode($code, $surname, $organisation);

        return new HtmlResponse($this->renderer->render('viewer::view-lpa', [
            'lpa' => $lpa->lpa,
        ]));
    }
}
