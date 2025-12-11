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
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Workflow\PaperVerificationCode;

/**
 * A base for our workflow for paper verification codes
 *
 * @codeCoverageIgnore
 * @template-implements WorkflowStep<PaperVerificationCode>
 */
abstract class AbstractPaperVerificationCodeHandler extends AbstractHandler implements
    CsrfGuardAware,
    LoggerAware,
    SessionAware,
    WorkflowStep
{
    use CsrfGuard;
    use Logger;
    use Session;
    /** @use State<PaperVerificationCode> */
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

    abstract public function isMissingPrerequisite(ServerRequestInterface $request): bool;

    /**
     * @inheritDoc
     */
    public function state(ServerRequestInterface $request): WorkflowState
    {
        return $this->loadState($request, PaperVerificationCode::class);
    }
}
