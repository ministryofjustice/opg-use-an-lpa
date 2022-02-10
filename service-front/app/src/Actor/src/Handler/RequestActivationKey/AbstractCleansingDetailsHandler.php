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
 * Class AbstractCleansingDetailsHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
abstract class AbstractCleansingDetailsHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    LoggerAware,
    WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;
    use State;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);
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
            || $this->state($request)->postcode === null;
    }

    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     *
     * @return RequestActivationKey
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }

    /**
     * @param RequestActivationKey $state
     *
     * @return bool
     */
    protected function hasFutureAnswersInState(RequestActivationKey $state): bool
    {
        $alwaysRequired = $state->telephone !== null || $state->noTelephone;

        if ($state->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
            return $alwaysRequired &&
                $state->donorFirstNames !== null &&
                $state->donorLastName !== null &&
                $state->donorDob !== null;
        }

        return $alwaysRequired;
    }
}
