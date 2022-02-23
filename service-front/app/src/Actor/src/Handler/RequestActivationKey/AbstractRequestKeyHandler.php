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
use Common\Workflow\StateAware;
use Common\Workflow\StateBuilderFactory;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowStep;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * Class AbstractRequestKeyHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
abstract class AbstractRequestKeyHandler extends AbstractHandler implements
    UserAware,
    CsrfGuardAware,
    SessionAware,
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
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);
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
     * @param ServerRequestInterface $request
     *
     * @return RequestActivationKey
     * @throws StateNotInitialisedException
     */
    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }
}
