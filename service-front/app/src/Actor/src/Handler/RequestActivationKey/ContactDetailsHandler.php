<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestContactDetails;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Handler\WorkflowStep;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;

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
                    'telephone' => $postData['telephone_option']['telephone'],
                    'no_phone' => $postData['telephone_option']['no_phone']
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
        $required = !$this->session->has('opg_reference_number')
            || !$this->session->has('first_names')
            || !$this->session->has('last_name')
            || !$this->session->has('dob')
            || !$this->session->has('postcode')
            || !$this->session->has('actor_role');

        $actorRole = $this->session->has('actor_role') ? $this->session->get('actor_role') : null;
        if ($actorRole === 'attorney') {
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
        $actorRole = $this->session->has('actor_role') ? $this->session->get('actor_role') : null;
        if ($actorRole === 'attorney') {
                return 'lpa.add.donor-details';
        }
        return 'lpa.add.actor-role';
    }
}
