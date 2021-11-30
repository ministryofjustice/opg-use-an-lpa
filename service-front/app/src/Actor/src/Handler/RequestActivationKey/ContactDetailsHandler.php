<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestContactDetails;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class ContactDetailsHandler extends AbstractCleansingDetailsHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    private RequestContactDetails $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestContactDetails($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($this->session->toArray());

        return new HtmlResponse($this->renderer->render('actor::contact-details', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session
            $this->session->set(
                'telephone_option',
                [
                    'telephone' => $postData['telephone_option']['telephone'] ?? null,
                    'no_phone' => $postData['telephone_option']['no_phone'] ?? null
                ]
            );

            return $this->redirectToRoute($this->nextPage());
        }

        return new HtmlResponse($this->renderer->render('actor::contact-details', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->getRouteNameFromAnswersInSession(true)
        ]));
    }

    public function isMissingPrerequisite(): bool
    {
        // If lpa is a full match and not cleansed then we need to short circuit the pre-requisite check
        if ($this->session->has('lpa_full_match_but_not_cleansed')) {
            return !$this->session->has('actor_id');
        }

        $required = parent::isMissingPrerequisite()
            || !$this->session->has('actor_role');

        if ($this->session->get('actor_role') === 'attorney') {
            return $required
                || !$this->session->has('donor_first_names')
                || !$this->session->has('donor_last_name')
                || !$this->session->has('donor_dob');
        }

        return $required;
    }

    public function nextPage(): string
    {
        return 'lpa.add.check-details-and-consent';
    }

    public function lastPage(): string
    {
        if ($this->session->get('actor_role') === 'attorney') {
                return 'lpa.add.donor-details';
        }

        if ($this->session->has('lpa_full_match_but_not_cleansed')) {
            return 'lpa.check-answers';
        }
        return 'lpa.add.actor-role';
    }
}
