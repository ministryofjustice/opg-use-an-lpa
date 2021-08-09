<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ActorRoleHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ActorRoleHandler extends AbstractHandler implements UserAware, CsrfGuardAware, SessionAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    /** @var LoggerInterface */
    protected $logger;
    private ActorRole $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper);
        $this->setAuthenticator($authenticator);
        $this->logger = $logger;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActorRole($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($request->getMethod() == 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-role',
            [
                'user'  => $this->user,
                'form'  => $this->form
            ]
        ));
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $selected = $this->form->getData()['actor_role_radio'];

            if ($selected === 'Donor') {
                $this->logger->info(
                    'User {id} identified as the Donor on the LPA after a partial match was found on their details',
                    [
                        'id' => $this->user->getIdentity()
                    ]
                );
                // these will have been set if the actor was the attorney for a previous request
                $this->session->unset('donor_firstnames');
                $this->session->unset('donor_lastname');
                $this->session->unset('donor_dob');
                return $this->redirectToRoute('lpa.add.contact-details');
            } else {
                $this->logger->info(
                    'User {id} identified as an Attorney on the LPA after a partial match was found on their details',
                    [
                        'id' => $this->user->getIdentity()
                    ]
                );
                return $this->redirectToRoute('lpa.add.donor-details');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/actor-role', [
            'user'  => $this->user,
            'form'  => $this->form
        ]));
    }
}
