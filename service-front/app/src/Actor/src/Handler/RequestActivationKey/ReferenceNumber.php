<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Handler\Traits\User;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Url\UrlValidityCheckService;
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
    private UrlValidityCheckService $urlValidityCheckService;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        UrlValidityCheckService $urlValidityCheckService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->urlValidityCheckService = $urlValidityCheckService;
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
        $this->form->setData($this->session->toArray());
        $referer = $this->urlValidityCheckService->setValidReferer($request->getHeaders()['referer'][0]);
        $this->session->set('referrer', $referer);


        return new HtmlResponse($this->renderer->render('actor::request-activation-key/reference-number', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'referer' => $referer
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
            'form' => $this->form->prepare(),
            'referer' => $this->session->get('referrer');
        ]));
    }

    private function routeFromAnswersInSession(): \Laminas\Diactoros\Response\RedirectResponse
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
}
