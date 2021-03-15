<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\AddLpaTriage;
use Common\Handler\{AbstractHandler, CsrfGuardAware, UserAware};
use Common\Handler\Traits\{CsrfGuard, User};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

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
        $form->setData($request->getParsedBody());

        if ($form->isValid()) {
            $selected = $form->getData()['activation_key_triage'];

            if ($selected === 'Yes') {
                return $this->redirectToRoute('lpa.add-by-code');
            }
            return $this->redirectToRoute('lpa.add-by-paper-information');
        }

        return new HtmlResponse($this->renderer->render('actor::add-lpa-triage', [
            'user' => $this->getUser($request),
            'form' => $form->prepare()
        ]));
    }
}
