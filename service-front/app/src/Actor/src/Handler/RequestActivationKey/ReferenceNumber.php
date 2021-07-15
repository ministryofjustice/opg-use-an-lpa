<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Actor\Form\RequestActivationKey\RequestReferenceNumber;
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
 * Class ReferenceNumber
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class ReferenceNumber extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    private RequestReferenceNumber $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestReferenceNumber($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getQueryParams()['clearSession']) {
            $this->clearSession();
        }

        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/reference-number', [
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
            $this->session->set('opg_reference_number', $postData['opg_reference_number']);
            return $this->routeFromAnswersInSession();
        }
        return new HtmlResponse($this->renderer->render('actor::request-activation-key/reference-number', [
            'user' => $this->user,
            'form' => $this->form->prepare()
        ]));
    }

    private function routeFromAnswersInSession(): RedirectResponse
    {
        if ($this->hasFutureAnswersInSession()) {
            return $this->redirectToRoute('lpa.check-answers');
        } else {
            return $this->redirectToRoute('lpa.your-name');
        }
    }

    private function hasFutureAnswersInSession(): bool
    {
        return $this->session->get('postcode') != null;
    }

    private function clearSession()
    {
        $this->session->unset('postcode');
        $this->session->unset('first_names');
        $this->session->unset('last_name');
        $this->session->unset('dob');
        $this->session->unset('opg_reference_number');
    }
}
