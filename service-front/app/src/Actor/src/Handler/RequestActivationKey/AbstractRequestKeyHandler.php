<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session as SessionTrait, UserAware};
use Common\Handler\Traits\User;
use Mezzio\Authentication\UserInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
abstract class AbstractRequestKeyHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
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

        if ($this->isSessionMissingPrerequisite()) {
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

    abstract protected function isSessionMissingPrerequisite(): bool;

    abstract protected function nextPage(): string;

    abstract protected function lastPage(): string;
}
