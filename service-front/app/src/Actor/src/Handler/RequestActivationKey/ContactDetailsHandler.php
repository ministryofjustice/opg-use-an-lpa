<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestContactDetails;
use Actor\Workflow\RequestActivationKey;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class ContactDetailsHandler extends AbstractCleansingDetailsHandler
{
    private RequestContactDetails $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestContactDetails($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'telephone_option' =>
                    [
                        'telephone' => $this->state($request)->telephone,
                        'no_phone' => $this->state($request)->noTelephone ? 'yes' : 'no'
                    ]
            ]
        );

        return new HtmlResponse($this->renderer->render(
            'actor::contact-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->telephone = $postData['telephone_option']['telephone'] ?? null;
            $this->state($request)->noTelephone = ($postData['telephone_option']['no_phone'] ?? null) === 'yes';

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render(
            'actor::contact-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        // If lpa is a full match and not cleansed then we need to short circuit the pre-requisite check
        if ($this->state($request)->needsCleansing) {
            return $this->state($request)->actorUid === null; // isMissing equals false if actorUid present
        }

        $required = parent::isMissingPrerequisite($request)
            || $this->state($request)->getActorRole() === null;

        if ($this->state($request)->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
            return $required
                || $this->state($request)->donorFirstNames === null
                || $this->state($request)->donorLastName === null
                || $this->state($request)->donorDob === null;
        }

        return $required;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.add.check-details-and-consent';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state **/
        if ($state->getActorRole() === RequestActivationKey::ACTOR_ATTORNEY) {
            return 'lpa.add.donor-details';
        }

        if ($state->needsCleansing) {
            return 'lpa.check-answers';
        }

        return 'lpa.add.actor-role';
    }
}
