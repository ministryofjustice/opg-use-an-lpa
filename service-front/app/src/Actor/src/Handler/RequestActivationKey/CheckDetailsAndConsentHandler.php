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
use Common\Service\Log\EventCodes;
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
 * Class CheckDetailsAndConsentHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class CheckDetailsAndConsentHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;

    private CheckDetailsAndConsent $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private array $data;
    private ?string $identity;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckDetailsAndConsent($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        if (!$this->hasRequiredSessionValues()) {
            throw new SessionTimeoutException();
        }

        if (!empty($telephone = $this->session->get('telephone_option')['telephone'])) {
            $this->data['telephone'] = $telephone;
        }

        if ($this->session->get('telephone_option')['no_phone'] === "yes") {
            $this->data['no_phone'] = true;
        }

        if (
            !($this->session->has('lpa_full_match_but_not_cleansed')) &&
            !($this->session->has('actor_id'))
        ) {
            $this->data['actor_role'] = $this->session->get('actor_role');

            if (strtolower($this->data['actor_role']) === 'attorney') {
                $this->data['donor_first_names'] = $this->session->get('donor_first_names');
                $this->data['donor_last_name'] = $this->session->get('donor_last_name');
                $this->data['donor_dob'] = Carbon::create(
                    $this->session->get('donor_dob')['year'],
                    $this->session->get('donor_dob')['month'],
                    $this->session->get('donor_dob')['day']
                )->toImmutable();
            }
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
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            // TODO: UML-1577
            $this->logger->notice(
                'User {id} has requested an activation key for their OOLPA ' .
                'and provided the following contact information: {role}, {phone}',
                [
                    'id'    => $this->user->getIdentity(),
                    'role'  => $this->data['actor_role'] === 'donor' ?
                        EventCodes::OOLPA_KEY_REQUESTED_FOR_DONOR :
                        EventCodes::OOLPA_KEY_REQUESTED_FOR_ATTORNEY,
                    'phone' => array_key_exists('telephone', $this->data) ?
                        EventCodes::OOLPA_PHONE_NUMBER_PROVIDED :
                        EventCodes::OOLPA_PHONE_NUMBER_NOT_PROVIDED
                ]
            );
        }
        return new HtmlResponse($this->renderer->render('actor::request-activation-key/check-details-and-consent', [
            'user'  => $this->user,
            'form'  => $this->form,
            'data'  => $this->data
        ]));
    }

    private function hasRequiredSessionValues(): bool
    {
        if (
            $this->session->has('lpa_full_match_but_not_cleansed') &&
            $this->session->has('actor_id')
        ) {
            return true;
        }
        $required = $this->session->has('opg_reference_number')
            || $this->session->has('first_names')
            || $this->session->has('last_name')
            || $this->session->has('dob')
            || $this->session->has('postcode')
            || $this->session->has('actor_role')
            || $this->session->has('telephone_option');

        if ($this->session->get('actor_role') === 'attorney') {
            return $required
                || $this->session->has('donor_first_names')
                || $this->session->has('donor_last_name')
                || $this->session->has('donor_dob');
        }
        return $required;
    }
}
