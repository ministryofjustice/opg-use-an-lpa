<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Workflow\RequestActivationKey;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    LoggerAware,
    SessionAware,
    Traits\CsrfGuard,
    Traits\Logger,
    Traits\Session as SessionTrait,
    UserAware};
use Common\Handler\Traits\User;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 * @template-implements WorkflowStep<RequestActivationKey>
 */
abstract class AbstractRequestKeyHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    SessionAware,
    LoggerAware,
    WorkflowStep
{
    use CsrfGuard;
    use Logger;
    use SessionTrait;
    /** @use State<RequestActivationKey> */
    use State;
    use User;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user    = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    /**
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): WorkflowState
    {
        return $this->loadState($request, RequestActivationKey::class);
    }
}
