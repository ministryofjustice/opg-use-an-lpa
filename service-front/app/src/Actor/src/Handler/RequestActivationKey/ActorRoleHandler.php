<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ActorRoleHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ActorRoleHandler extends AbstractCleansingDetailsHandler
{
    private ActorRole $form;

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
        if ($this->state($request)->getActorRole() === RequestActivationKey::ACTOR_DONOR) {
            $this->form->setData(['actor_role_radio' => 'Donor']);
        } elseif ($this->state($request)->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
            $this->form->setData(['actor_role_radio' => 'Attorney']);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/actor-role',
            [
                'user'  => $this->user,
                'form'  => $this->form,
                'back' => $this->lastPage($this->state($request))
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
                $this->state($request)->setActorRole(RequestActivationKey::ACTOR_DONOR);
            } elseif ($selected === 'Attorney') {
                $this->state($request)->setActorRole(RequestActivationKey::ACTOR_ATTORNEY);
            }

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/actor-role', [
            'user' => $this->user,
            'form' => $this->form,
            'back' => $this->lastPage($this->state($request))
        ]));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return parent::isMissingPrerequisite($request)
            || $this->state($request)->actorAddress1 === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        if ($this->hasFutureAnswersInState($state)) {
            return 'lpa.add.check-details-and-consent';
        }

        return $state->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY
            ? 'lpa.add.donor-details'
            : 'lpa.add.contact-details';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.actor-address';
    }
}
