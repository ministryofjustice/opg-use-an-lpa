<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Handler\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActorRoleHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ActorRoleHandler extends AbstractCleansingDetailsHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActorRole($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-role',
            [
                'user'  => $this->user,
                'form'  => $this->form,
                'back' => $this->getRouteNameFromAnswersInSession(true)
            ]
        ));
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $selected = $this->form->getData()['actor_role_radio'];

            if ($selected === 'Donor') {
                $this->session->set('actor_role', 'donor');
            } elseif ($selected === 'Attorney') {
                $this->session->set('actor_role', 'attorney');
            }
            $nextPageName = $this->getRouteNameFromAnswersInSession();
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/actor-role', [
            'user'  => $this->user,
            'form'  => $this->form,
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function isMissingPrerequisite(): bool
    {
        return parent::isMissingPrerequisite();
    }

    public function nextPage(): string
    {
        if ($this->session->get('actor_role') === 'attorney') {
            return 'lpa.add.donor-details';
        }
        return 'lpa.add.contact-details';
    }

    public function lastPage(): string
    {
        return 'lpa.check-answers';
    }
}
