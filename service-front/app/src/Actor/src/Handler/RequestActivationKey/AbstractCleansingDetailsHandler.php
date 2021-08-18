<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Handler\WorkflowStep;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractCleansingDetailsHandler extends AbstractHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    protected ?SessionInterface $session;
    protected ?UserInterface $user;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->logger = $logger;
    }

    /**
     * @param bool $back optional parameter specifying if the route named should be for the back button
     *
     * @return string the name of the route
     */
    protected function getRouteNameFromAnswersInSession(bool $back = false): string
    {
        if ($this->hasFutureAnswersInSession()) {
            return 'lpa.add.check-details-and-consent';
        } else {
            return $back ? $this->lastPage() : $this->nextPage();
        }
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

        if ($this->isMissingPrerequisite()) {
            return $this->redirectToRoute('lpa.add.actor-role');
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
        $s = $this->session->toArray();

        $alwaysRequired = (
            !empty($s['telephone_option']['telephone']) ||
            $s['telephone_option']['no_phone'] === 'yes'
            ) ?? false;

        if ($this->session->get('actor_role') === 'attorney') {
            return $alwaysRequired &&
                $this->session->has('donor_first_names') &&
                $this->session->has('donor_last_name') &&
                $this->session->has('donor_dob')
            ;
        }
        return $alwaysRequired;
    }
}
