<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\Triage;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\CsrfGuardAware;
use Mezzio\Helper\ServerUrlHelper;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;


/**
 * Class ActorTriagePageHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ActorTriagePageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper
    )
    {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Triage($this->getCsrfGuard($request));

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render('actor::home-page', [
            'form' => $form->prepare()
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new Triage($this->getCsrfGuard($request));
        $requestData = $request->getParsedBody();

        $form->setData($requestData);

        if ($form->isValid()) {
            $fromData = $form->getData();

            if ($fromData['triageEntry'] === 'yes') {
                return $this->redirectToRoute('login');
            }
            return $this->redirectToRoute('create-account');
        }

        return new HtmlResponse($this->renderer->render('actor::home-page', [
            'form' => $form->prepare()
        ]));
    }
}
