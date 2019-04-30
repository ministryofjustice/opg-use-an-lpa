<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class EnterCodeHandler
 * @package Viewer\Handler
 */
class EnterCodeHandler extends AbstractHandler
{
    /** @var LpaService */
    private $lpaService;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     * @param FormFactoryInterface|null $formFactory
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $s = $this->getSession($request,'session');

        $s->set('test', 'hello');

        $body = $request->getParsedBody();
        if (isset($body['lpa_code'])) {
            $lpa = $this->lpaService->getLpaByCode($body['lpa_code']);

            $session = $this->getSession($request,'session');
            $session->set('code', $data['lpa_code']);

            return $this->redirectToRoute('check-code');
        }

        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $token = $guard->generateToken();

        return new HtmlResponse(
            $this->renderer->render(
                'app::enter-code',
                [ 'csrf_token' => $token ]
            )
        );
    }
}
