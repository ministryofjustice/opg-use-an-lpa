<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\AttorneyDetailsForPV;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class ProvideAttorneyDetailsForPVHandler extends AbstractPVSCodeHandler
{
    private AttorneyDetailsForPV $form;

    private const TEMPLATE = 'viewer::paper-verification/provide-attorney-details';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new AttorneyDetailsForPV($this->getCsrfGuard($request));

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
            $this->form->setData(['attorneys_name' => $attorneyName]);
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
            $this->state($request)->attorneyName  = $this->form->getData()['attorneys_name'];

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
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasFutureAnswersInState(PaperVerificationCode $state): bool
    {
        return
            $state->sentToDonor !== null &&
            $state->lastName !== null &&
            $state->lpaUid !== null &&
            $state->code !== null;
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
        return $this->hasFutureAnswersInState($state)
            ? 'pv.check-answers'
            : 'pv.donor-dob';
    }
}
