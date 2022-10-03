<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestDateOfBirth;
use Actor\Workflow\RequestActivationKey;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 */
class DateOfBirthHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestDateOfBirth $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestDateOfBirth($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        /** @var DateTimeImmutable $dob */
        if (($dob = $this->state($request)->dob) !== null) {
            $this->form->setData(
                [
                    'dob' => [
                        'day'   => $dob->format('d'),
                        'month' => $dob->format('m'),
                        'year'  => $dob->format('Y'),
                    ],
                ]
            );
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/date-of-birth', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            // Set the data in the session and pass to the check your answers handler
            $this->state($request)->dob = (new DateTimeImmutable())->setDate(
                (int) $postData['dob']['year'],
                (int) $postData['dob']['month'],
                (int) $postData['dob']['day']
            );

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/date-of-birth', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null;
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state */
        return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.your-name';
    }

    public function nextPage(WorkflowState $state): string
    {
        /** @var RequestActivationKey $state */
        return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.postcode';
    }
}
