<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestNames;
use Actor\Workflow\RequestActivationKey;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 * @template-implements WorkflowStep<RequestActivationKey>
 */
class NameHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestNames $form;
    private bool $isModernised;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $referenceNumber    = $this->state($request)->referenceNumber;
        $this->isModernised = $referenceNumber !== null && $referenceNumber[0] === 'M';
        $this->form         = new RequestNames($this->getCsrfGuard($request), $this->isModernised);
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'first_names' => $this->state($request)->firstNames,
                'last_name'   => $this->state($request)->lastName,
            ]
        );

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user'         => $this->user,
            'form'         => $this->form->prepare(),
            'back'         => $this->lastPage($this->state($request)),
            'isModernised' => $this->isModernised,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the state and pass to the check your answers handler
            $this->state($request)->firstNames = $postData['first_names'];
            $this->state($request)->lastName   = $postData['last_name'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user'         => $this->user,
            'form'         => $this->form->prepare(),
            'back'         => $this->lastPage($this->state($request)),
            'isModernised' => $this->isModernised,
        ]));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.date-of-birth';
    }

    public function lastPage(WorkflowState $state): string
    {
        return $state->postcode !== null ? 'lpa.check-answers' : 'lpa.add-by-paper';
    }
}
