<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestNames;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RequestActivationKeyHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class NameHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestNames $form;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestNames($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'first_names' => $this->state($request)->firstNames,
                'last_name' => $this->state($request)->lastName
            ]
        );

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request))
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the state and pass to the check your answers handler
            $this->state($request)->firstNames = $postData['first_names'];
            $this->state($request)->lastName = $postData['last_name'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render('actor::request-activation-key/your-name', [
            'user' => $this->user,
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request))
        ]));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return ! $this->state($request)->has('referenceNumber');
    }

    public function nextPage(WorkflowState $state): string
    {
        return $state->has('postcode') ? 'lpa.check-answers' : 'lpa.date-of-birth';
    }

    public function lastPage(WorkflowState $state): string
    {
        return $state->has('postcode') ? 'lpa.check-answers' : 'lpa.add-by-paper';
    }
}
