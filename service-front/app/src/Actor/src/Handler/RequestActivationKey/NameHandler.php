<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Actor\Form\RequestActivationKey\RequestNames;
use Common\Handler\Traits\User;
use Common\Service\Url\UrlValidityCheckService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class NameHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware
{
    private RequestNames $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestNames($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare()
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session and pass to the check your answers handler
            $this->session->set('first_names', $postData['first_names']);
            $this->session->set('last_name', $postData['last_name']);
            return $this->routeFromAnswersInSession();
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare()
        ]));
    }

    private function routeFromAnswersInSession(): RedirectResponse
    {
        if ($this->hasFutureAnswersInSession()) {
            return $this->redirectToRoute('lpa.check-answers');
        } else {
            return $this->redirectToRoute('lpa.date-of-birth');
        }
    }
}
