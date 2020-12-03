<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\AddLpaTriage;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Mezzio\Authentication\AuthenticationInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Helper\UrlHelper;

/**
 * Class AddLpaTriageHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class AddLpaTriageHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new AddLpaTriage($this->getCsrfGuard($request));

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render('actor::add-lpa-triage', [
            'user' => $this->getUser($request),
            'form' => $form->prepare()
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new AddLpaTriage($this->getCsrfGuard($request));
        $requestData = $request->getParsedBody();

        $form->setData($requestData);

        if ($form->isValid()) {

            if ($form->getData()['activation_key_triage'] === 'Yes') {
                return $this->redirectToRoute('lpa.add-by-code');
            }
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        return new HtmlResponse($this->renderer->render('actor::add-lpa-triage', [
            'user' => $this->getUser($request),
            'form' => $form->prepare()
        ]));
    }
}
