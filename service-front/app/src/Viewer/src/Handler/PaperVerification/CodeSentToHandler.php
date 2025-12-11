<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\Features\FeatureEnabled;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\CodeSentTo;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class CodeSentToHandler extends AbstractPaperVerificationCodeHandler
{
    private CodeSentTo $form;

    private const TEMPLATE = 'viewer::paper-verification/code-sent-to';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CodeSentTo($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $sentToDonor  = $this->state($request)->sentToDonor;
        $attorneyName = $this->state($request)->attorneyName;

        if ($sentToDonor !== null) {
            $this->form->setData(['code_sent_to' => $sentToDonor === false ? 'Attorney' : 'Donor']);
        }

        if ($attorneyName) {
            $this->form->setData(['attorney_name' => $attorneyName]);
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'donorName' => $this->state($request)->donorName,
            'form'      => $this->form->prepare(),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $storedSentToDonor = $this->state($request)->sentToDonor;
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $sentToDonor = $this->form->getData()['code_sent_to'] === 'Donor';

            if ($storedSentToDonor !== null && $storedSentToDonor !== $sentToDonor) {
                $this->state($request)->noOfAttorneys = null;
                $this->state($request)->attorneyName  = null;
                $this->state($request)->dateOfBirth   = null;
            }

            if (!$this->state($request)->sentToDonor = $sentToDonor) {
                $this->state($request)->attorneyName = $this->form->getData()['attorney_name'];
            }

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'donorName' => $this->state($request)->donorName,
            'form'      => $this->form->prepare(),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->lastName === null
            || $this->state($request)->code === null
            || $this->state($request)->lpaUid === null;
    }

    /**
     * @inheritDoc
     */
    public function hasFutureAnswersInState(PaperVerificationCode $state): bool
    {
        return
            $state->noOfAttorneys !== null &&
            $state->dateOfBirth !== null &&
            $state->lastName !== null &&
            $state->lpaUid !== null &&
            $state->code !== null &&
            $state->attorneyName !== null;
    }

    /**
     * @inheritDoc
    */
    public function nextPage(WorkflowState $state): string
    {
        if ($this->hasFutureAnswersInState($state)) {
            return 'pv.check-answers';
        }

        return $state->sentToDonor ? 'pv.donor-date-of-birth' : 'pv.attorney-date-of-birth';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'home';
    }
}
