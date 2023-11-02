<?php

declare(strict_types=1);

namespace Actor\Handler\AddLpa;

use Actor\Workflow\AddLpa;
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
use Common\Workflow\WorkflowStep;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractAddLpaHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    SessionAware,
    LoggerAware,
    WorkflowStep
{
    use CsrfGuard;
    use Logger;
    use SessionTrait;
    use State;
    use User;

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

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add-by-key');
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    /**
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    /**
     * @param  ServerRequestInterface $request
     * @return AddLpa
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): AddLpa
    {
        return $this->loadState($request, AddLpa::class);
    }
}
