<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckDetailsAndConsent;
use Carbon\Carbon;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Features\FeatureEnabled;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class CheckDetailsAndConsentHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;

    private ?SessionInterface $session;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        FeatureEnabled $featureEnabled
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->featureEnabled = $featureEnabled;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckDetailsAndConsent($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        if (
            is_null($this->session)
            || is_null($this->session->get('actor_role'))
            || (
                is_null($this->session->get('telephone_option')['telephone'])
                && is_null($this->session->get('telephone_option')['no_phone'])
            )
        ) {
            throw new SessionTimeoutException();
        }

        $this->data['actor_role'] = $this->session->get('actor_role');

        if (!empty($telephone = $this->session->get('telephone_option')['telephone'])) {
            $this->data['telephone'] = $telephone;
        }

        if (strtolower($this->session->get('actor_role')) === 'attorney') {
            if (
                is_null($this->session->get('donor_first_names')) ||
                is_null($this->session->get('donor_last_name')) ||
                is_null($this->session->get('donor_dob')['day']) ||
                is_null($this->session->get('donor_dob')['month']) ||
                is_null($this->session->get('donor_dob')['year'])
            ) {
                throw new SessionTimeoutException();
            }

            $this->data['donor_first_names'] = $this->session->get('donor_first_names');
            $this->data['donor_last_name'] = $this->session->get('donor_last_name');
            $this->data['donor_dob'] = Carbon::create(
                $this->session->get('donor_dob')['year'],
                $this->session->get('donor_dob')['month'],
                $this->session->get('donor_dob')['day']
            )->toImmutable();
        }

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('actor::request-activation-key/check-details-and-consent', [
            'user'  => $this->user,
            'form'  => $this->form,
            'data'  => $this->data
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('actor::request-activation-key/check-details-and-consent', [
            'user'  => $this->user,
            'form'  => $this->form,
            'data'  => $this->data
        ]));
    }
}
