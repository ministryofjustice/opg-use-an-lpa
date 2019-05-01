<?php

declare(strict_types=1);

namespace Viewer\Handler;

use ArrayObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Viewer\Service\Lpa\LpaService;
use Zend\Expressive\Helper\UrlHelper;
use Viewer\Form\ShareCode;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

use Viewer\Form\EnterCode;

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
        FormFactoryInterface $formFactory,
        ShareCode $form
    )
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
        // use a trait to create the form we need.
        $form = $this->createForm($request, $this->formFactory, ShareCode::class);

        //$form = $this->formFactory->create(ShareCode::class, null);

        //$csrf = $form->getConfig()->getOption('csrf_token_manager');

        //$form->getConfig()->op

        // this bit of magic handles the form using the default provider, which
        // accesses the raw super globals to populate. what we really want is a
        // PSR7 provider.
        // TODO as a part of UML-105
        //$form->handleRequest();

        if ($request->getMethod() == 'POST') {

            $form->submit($request->getParsedBody()[$form->getName()]);

            if ($form->isValid()) {
                $data = $form->getData();

                $session = $this->getSession($request,'session');
                $session->set('code', $data['lpa_code']);

                return $this->redirectToRoute('check-code');
            }

        }

        return new HtmlResponse($this->renderer->render('app::enter-code', [
            'form' => $form->createView(),
        ]));
    }
}
