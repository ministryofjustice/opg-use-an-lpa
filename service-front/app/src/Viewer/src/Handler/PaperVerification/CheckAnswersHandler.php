<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Exception\ApiException;
use Common\Service\Lpa\PaperVerificationCodeService;
use Common\Service\Lpa\PaperVerificationCodeStatus;
use Common\Workflow\WorkflowState;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\CheckAnswers;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class CheckAnswersHandler extends AbstractPVSCodeHandler
{
    public const TEMPLATE = 'viewer::paper-verification/check-answers';

    private CheckAnswers $form;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private PaperVerificationCodeService $paperVerificationCodeService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckAnswers($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $stateData = $this->state($request);

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'          => $this->form,
            'lpaUid'        => $stateData->lpaUid,
            'sentToDonor'   => $stateData->sentToDonor,
            'dateOfBirth'   => $stateData->dateOfBirth,
            'noOfAttorneys' => $stateData->noOfAttorneys,
            'attorneyName'  => $stateData->attorneyName,
            'donorName'     => $stateData->donorName,
            'back'          => $this->lastPage($stateData),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        $stateData = $this->state($request);

        if ($this->form->isValid()) {
            $result = $this->paperVerificationCodeService->validate(
                $stateData->code->value,
                $stateData->lastName,
                $stateData->lpaUid,
                $stateData->sentToDonor,
                $stateData->attorneyName,
                $stateData->dateOfBirth,
                $stateData->noOfAttorneys
            );

            if ($result->status === PaperVerificationCodeStatus::NOT_FOUND) {
                return new HtmlResponse(
                    $this->renderer->render('viewer::paper-verification/cannot-show-lpa', [
                        'donorName'     => $stateData->donorName,
                        'lpaUid'        => $stateData->lpaUid,
                        'sentToDonor'   => $stateData->sentToDonor,
                        'dateOfBirth'   => $stateData->dateOfBirth->format('Y-m-d'),
                        'noOfAttorneys' => $stateData->noOfAttorneys,
                        'attorneyName'  => $stateData->attorneyName,
                    ])
                );
            }

            if ($result->status === PaperVerificationCodeStatus::OK) {
                return $this->redirectToRoute($this->nextPage($stateData));
            }
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'          => $this->form,
            'lpaUid'        => $stateData->lpaUid,
            'sentToDonor'   => $stateData->sentToDonor,
            'dateOfBirth'   => $stateData->dateOfBirth,
            'noOfAttorneys' => $stateData->noOfAttorneys,
            'attorneyName'  => $stateData->attorneyName,
            'donorName'     => $stateData->donorName,
            'back'          => $this->lastPage($stateData),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->lastName === null
        || $this->state($request)->code === null
        || $this->state($request)->lpaUid === null
        || $this->state($request)->sentToDonor === null
        || $this->state($request)->attorneyName === null
        || $this->state($request)->dateOfBirth === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return 'pv.enter-organisation-name';
    }

    /**
     * @return string The route name of the previous page in the workflow
     */
    public function lastPage(WorkflowState $state): string
    {
        /** @var PaperVerificationCode $state */
        return $state->sentToDonor === false
            ? 'pv.number-of-attorneys'
            : 'pv.provide-attorney-details';
    }
}
