<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Workflow\RequestActivationKey;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowStep;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractCleansingDetailsHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    LoggerAware,
    WorkflowStep
{
    use CsrfGuard;
    use Logger;
    use SessionTrait;
    use State;
    use User;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user    = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add.actor-role');
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null
            || $this->state($request)->lastName === null
            || $this->state($request)->dob === null
            || $this->state($request)->liveInUK === null;
    }

    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @return RequestActivationKey
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }

    protected function hasFutureAnswersInState(RequestActivationKey $state): bool
    {
        // address 1 is a required field on it's page so only need to check that.
        $alwaysRequired = $state->actorAddress1 !== null || $state->actorAbroadAddress !== null;

        if ($state->actorAddressResponse === RequestActivationKey::ACTOR_ADDRESS_SELECTION_NO) {
            $alwaysRequired = $alwaysRequired && $state->addressOnPaper !== null;
        }

        $alwaysRequired = $alwaysRequired && $state->getActorRole() !== null;

        if ($state->getActorRole() === RequestActivationKey::ACTOR_TYPE_ATTORNEY) {
            $alwaysRequired =  $alwaysRequired &&
                $state->donorFirstNames !== null &&
                $state->donorLastName !== null &&
                $state->donorDob !== null;
        }

        if ($state->getActorRole() === RequestActivationKey::ACTOR_TYPE_DONOR) {
            $alwaysRequired =  $alwaysRequired &&
                $state->attorneyFirstNames !== null &&
                $state->attorneyLastName !== null &&
                $state->attorneyDob !== null;
        }

        return $alwaysRequired
            && ($state->telephone !== null || $state->noTelephone);
    }
}
