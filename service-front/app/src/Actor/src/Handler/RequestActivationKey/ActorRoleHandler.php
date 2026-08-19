<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Actor\Workflow\RequestActivationKey;
use Actor\Workflow\ActorAddressResponse;
use Actor\Workflow\ActorType;
use Common\Service\Log\EventCodes;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ActorRoleHandler extends AbstractCleansingDetailsHandler
{
    private ActorRole $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new ActorRole($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->state($request)->getActorRole() === ActorType::DONOR) {
            $this->form->setData(['actor_role_radio' => 'Donor']);
        } elseif ($this->state($request)->getActorRole() === ActorType::ATTORNEY) {
            $this->form->setData(['actor_role_radio' => 'Attorney']);
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/actor-role',
                [
                    'user' => $this->user,
                    'form' => $this->form,
                    'back' => $this->lastPage($this->state($request)),
                ]
            )
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $selected = $this->form->getData()['actor_role_radio'];

            return match ($selected) {
                'Donor', 'Attorney' => $this->checkDonorOrAttorney($request, $selected),
                'ReplacementAttorney' => $this->nextPageWhenRoleIsReplacementAttorney($request),
            };
        }

        return new HtmlResponse(
            $this->renderer->render('actor::request-activation-key/actor-role', [
                'user' => $this->user,
                'form' => $this->form,
                'back' => $this->lastPage($this->state($request)),
            ])
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return parent::isMissingPrerequisite($request) ||
            ($this->state($request)->actorAddress1 === null && $this->state($request)->actorAbroadAddress === null);
    }

    public function nextPage(WorkflowState $state): string
    {
        if ($this->hasFutureAnswersInState($state)) {
            return 'lpa.add.check-details-and-consent';
        }

        return $state->getActorRole() === ActorType::ATTORNEY
            ? 'lpa.add.donor-details'
            : 'lpa.add.attorney-details';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state * */
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : $this->lastPageByPreviousAnswers(
                $state->getActorAddressResponse() === ActorAddressResponse::NO
            );
    }

    private function lastPageByPreviousAnswers(bool $filledAddressOnPaper): string
    {
        return $filledAddressOnPaper ? 'lpa.add.address-on-paper' : 'lpa.add.actor-address';
    }

    private function checkDonorOrAttorney(ServerRequestInterface $request, string $selected): RedirectResponse
    {
        $this->state($request)->setActorRole(
            $selected === 'Donor' ?
                ActorType::DONOR->value : ActorType::ATTORNEY->value
        );
        return $this->redirectToRoute($this->nextPage($this->state($request)));
    }

    private function nextPageWhenRoleIsReplacementAttorney(ServerRequestInterface $request): HtmlResponse
    {
        $this->logger->notice(
            'Request for activation key made by replacement attorney by user {user}',
            [
                'event_code' => EventCodes::ACTIVATION_KEY_REQUEST_REPLACEMENT_ATTORNEY,
                'user'       => $this->user->getIdentity(),
            ]
        );

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/stop-replacement-attorney',
                ['user' => $this->user]
            )
        );
    }
}
