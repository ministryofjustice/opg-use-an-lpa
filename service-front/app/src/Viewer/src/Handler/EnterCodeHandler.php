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
use Viewer\Form\ShareCodeForm;
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

    private $form;

    /**
     * EnterCodeHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param LpaService $lpaService
     * @param ShareCodeForm $form
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LpaService $lpaService,
        ShareCodeForm $form
    )
    {
        parent::__construct($renderer, $urlHelper);

        $this->form = $form;
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->form->setCsrfToken('1234');  // This string will be pulled from the session.

        if ($request->getMethod() == 'POST') {

            $this->form->submit($request->getParsedBody()[$this->form->getName()]);

            if ($this->form->isValid()) {
                $data = $this->form->getData();

                $session = $this->getSession($request,'session');
                $session->set('code', $data['lpa_code']);

                return $this->redirectToRoute('check-code');
            }

        }

        return new HtmlResponse($this->renderer->render('app::enter-code', [
            'form' => $this->form->createView(),
        ]));
    }
}






// $csrf = $form->getConfig()->getOption('csrf_token_manager');