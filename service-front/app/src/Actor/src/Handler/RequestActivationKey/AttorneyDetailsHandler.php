<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\AttorneyDetails;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Workflow\State;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class AttorneyDetailsHandler extends AbstractCleansingDetailsHandler
{
    use CsrfGuard;
    use SessionTrait;
    use State;
    use User;

    private AttorneyDetails $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new AttorneyDetails($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'attorney_first_names' => $this->state($request)->attorneyFirstNames,
            'attorney_last_name'   => $this->state($request)->attorneyLastName,
        ];

        if (($dob = $this->state($request)->attorneyDob) !== null) {
            $data['attorney_dob'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];
        }

        $this->form->setData($data);

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/attorney-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request)),
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->attorneyFirstNames = $postData['attorney_first_names'];
            $this->state($request)->attorneyLastName   = $postData['attorney_last_name'];
            $this->state($request)->attorneyDob        = (new DateTimeImmutable())->setDate(
                (int) $postData['attorney_dob']['year'],
                (int) $postData['attorney_dob']['month'],
                (int) $postData['attorney_dob']['day']
            );

            $nextPageName = $this->nextPage($this->state($request));

            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/attorney-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request)),
            ]
        ));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return parent::isMissingPrerequisite($request)
            || $this->state($request)->getActorRole() === null
            || $this->state($request)->getActorRole() !== 'donor';
    }

    public function nextPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.contact-details';
    }

    public function lastPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state)
            ? 'lpa.add.check-details-and-consent'
            : 'lpa.add.actor-role';
    }
}
