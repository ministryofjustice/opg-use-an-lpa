<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\LoggerAware;
use Common\Handler\SessionAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\Session;
use Common\Workflow\State;
use Common\Workflow\WorkflowStep;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Workflow\PaperVerificationShareCode;

/**
 * A base for our workflow for both Share Code and Paper Verification codes
 *
 * Abstract Paper Verification Share Code Handler
 */
abstract class AbstractPVSCodeHandler extends AbstractHandler implements
    CsrfGuardAware,
    SessionAware,
    LoggerAware,
    WorkflowStep
{
    use CsrfGuard;
    use Logger;
    use Session;
    use State;

    protected ?SessionInterface $session;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('home');
        }

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    abstract public function handleGet(ServerRequestInterface $request): ResponseInterface;

    abstract public function handlePost(ServerRequestInterface $request): ResponseInterface;

    /**
     * @inheritDoc
     */
    public function state(ServerRequestInterface $request): PaperVerificationShareCode
    {
        return $this->loadState($request, PaperVerificationShareCode::class);
    }
}