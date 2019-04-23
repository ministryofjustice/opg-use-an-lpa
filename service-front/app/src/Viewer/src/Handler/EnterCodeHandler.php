<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class EnterCodeHandler
 * @package Viewer\Handler
 */
class EnterCodeHandler extends AbstractHandler
{
    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     */
    public function __construct(TemplateRendererInterface $renderer, UrlHelper $urlHelper, LpaService $lpaService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $s = $this->getSession($request,'session');

        $s->set('test', 'hello');

        $errorMsg = null;

        if ($request->getMethod() == 'POST') {
            $post = $request->getParsedBody();

            //  TODO - Validation required....

            if (isset($post['share-code'])) {
                $lpa = $this->lpaService->getLpaByCode($post['share-code']);

                if ($lpa instanceof \ArrayObject) {
                    return $this->redirectToRoute('view-lpa', [
                        'id' => $lpa->id,
                    ]);
                } else {
                    $errorMsg = 'No LPA were found using the provided code';
                }
            }
        }

        return new HtmlResponse($this->renderer->render('app::enter-code', [
            'errorMsg' => $errorMsg,
        ]));
    }
}
