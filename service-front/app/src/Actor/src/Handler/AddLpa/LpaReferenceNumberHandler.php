<?php

declare(strict_types=1);

namespace Actor\Handler\AddLpa;

use Actor\Form\AddLpa\LpaReferenceNumber;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class LpaReferenceNumberHandler extends AbstractAddLpaHandler
{
    private LpaReferenceNumber $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new LpaReferenceNumber($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData(
            [
                'reference_number' => $this->state($request)->lpaReferenceNumber,
            ]
        );

        return new HtmlResponse(
            $this->renderer->render('actor::add-lpa/lpa-reference-number', [
                'form' => $this->form->prepare(),
                'user' => $this->getUser($request),
                'back' => $this->lastPage($this->state($request)),
            ])
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->lpaReferenceNumber = $postData['reference_number'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse(
            $this->renderer->render('actor::add-lpa/lpa-reference-number', [
                'form' => $this->form->prepare(),
                'user' => $this->getUser($request),
                'back' => $this->lastPage($this->state($request)),
            ])
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->activationKey === null
            || $this->state($request)->dateOfBirth === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.check';
    }

    public function lastPage(WorkflowState $state): string
    {
        return 'lpa.add-by-key.date-of-birth';
    }
}
