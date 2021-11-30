<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Workflow\RequestActivationKey;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    SessionAware,
    Traits\CsrfGuard,
    Traits\Session as SessionTrait,
    UserAware};
use Common\Handler\Traits\User;
use Common\Workflow\State;
use Common\Workflow\StateAware;
use Common\Workflow\StateBuilderFactory;
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
    StateAware,
    WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use State;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;
    protected StateBuilderFactory $stateFactory;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        StateBuilderFactory $stateFactory
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->logger = $logger;
        $this->stateFactory = $stateFactory;
    }

    /**
     * @inheritDoc
     */
    public function stateFactory(): callable
    {
        return ($this->stateFactory)(RequestActivationKey::class);
    }

    /**
     * @param bool $back optional parameter specifying if the route named should be for the back button
     *
     * @return string the name of the route
     */
    protected function getRouteNameFromAnswersInSession(bool $back = false): string
    {
        if ($this->hasFutureAnswersInSession()) {
            return 'lpa.check-answers';
        } else {
            return $back ? $this->lastPage() : $this->nextPage();
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite()) {
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    protected function hasFutureAnswersInSession(): bool
    {
        return $this->session->has('postcode');
    }
}
