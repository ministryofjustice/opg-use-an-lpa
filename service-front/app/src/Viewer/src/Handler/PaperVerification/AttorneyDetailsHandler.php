<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\AttorneyDetails;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class AttorneyDetailsHandler extends AbstractPaperVerificationCodeHandler
{
    private AttorneyDetails $form;

    private const TEMPLATE = 'viewer::paper-verification/attorney-details';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new AttorneyDetails($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $attorneyName  = $this->state($request)->attorneyName;
        $noOfAttorneys = $this->state($request)->noOfAttorneys;

        if ($noOfAttorneys) {
            $this->form->setData(['no_of_attorneys' => $noOfAttorneys]);
        }

        if ($noOfAttorneys) {
            $this->form->setData(['attorney_name' => $attorneyName]);
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->state($request)->noOfAttorneys = $this->form->getData()['no_of_attorneys'];
            $this->state($request)->attorneyName  = $this->form->getData()['attorney_name'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form' => $this->form->prepare(),
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->sentToDonor !== true
            || $this->state($request)->dateOfBirth === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return 'pv.check-answers';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return $this->shouldCheckAnswers($state) ? 'pv.check-answers' : 'pv.donor-date-of-birth';
    }
}
