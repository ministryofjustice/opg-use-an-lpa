<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Expressive\Helper\UrlHelper;
use Viewer\Form\ShareCode;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use ArrayObject;

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
        LpaService $lpaService,
        FormFactoryInterface $formFactory = null)
    {
        parent::__construct($renderer, $urlHelper, $formFactory);

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

        // use a trait to create the form we need.
        $form = $this->createForm($request, $this->formFactory, ShareCode::class);

        // this bit of magic handles the form using the default provider, which
        // accesses the raw super globals to populate. what we really want is a
        // PSR7 provider.
        // TODO as a part of UML-105
        $form->handleRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $lpa = $this->lpaService->getLpaByCode($data['lpa_code']);

            if ($lpa instanceof ArrayObject) {
                return $this->redirectToRoute('view-lpa', [
                    'id' => $lpa->id,
                ]);
            }
        }

        return new HtmlResponse($this->renderer->render('app::enter-code', [
            'form' => $form->createView(),
        ]));
    }
}
