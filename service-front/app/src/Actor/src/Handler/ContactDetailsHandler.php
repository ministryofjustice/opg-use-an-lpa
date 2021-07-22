<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\RequestContactDetails;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Url\UrlValidityCheckService;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * @codeCoverageIgnore
 */
class ContactDetailsHandler extends AbstractHandler implements UserAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    private UrlValidityCheckService $urlValidityCheckService;
    private RequestContactDetails $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private TranslatorInterface $translator;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        UrlValidityCheckService $urlValidityCheckService,
        TranslatorInterface $translator
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authentication);
        $this->urlValidityCheckService = $urlValidityCheckService;
        $this->translator = $translator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestContactDetails($this->getCsrfGuard($request));
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
        $this->session->set('referer', $referer);

        return new HtmlResponse($this->renderer->render('actor::contact-details', [
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

            //  Set the data in the session
            $this->session->set('telephone', $postData['telephone']);
            $this->session->set('no_phone', $postData['no_phone']);

            //TODO: Redirect to end of journey
            //return $this->redirectToRoute('Somewhere');
        }

        return new HtmlResponse($this->renderer->render('actor::contact-details', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'referer' => $this->session->get('referer')
        ]));
    }
}