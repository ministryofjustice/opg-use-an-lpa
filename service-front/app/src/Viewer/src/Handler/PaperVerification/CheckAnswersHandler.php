<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Exception\ApiException;
use Common\Service\SystemMessage\SystemMessageService;
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
use Viewer\Workflow\PaperVerificationShareCode;
use Common\Service\Lpa\LpaService;

/**
 * @codeCoverageIgnore
 */
class CheckAnswersHandler extends AbstractPVSCodeHandler
{
    private CheckAnswers $form;

    public const TEMPLATE = 'viewer::paper-verification/check-answers';
    /**
     * @var array{
     *     "view/en": string,
     *     "view/cy": string,
     * }
     */
    private array $systemMessages;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private SystemMessageService $systemMessageService,
        private LpaService $lpaService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form           = new CheckAnswers($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

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
            'en_message'    => $this->systemMessages['view/en'] ?? null,
            'cy_message'    => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        $stateData = $this->state($request);

        if ($this->form->isValid()) {
            $requiredFields = [
                'code',
                'lastName',
                'lpaUid',
                'sentToDonor',
                'attorneyName',
                'dateOfBirth',
                'noOfAttorneys',
            ];

            foreach ($requiredFields as $field) {
                if (empty($stateData->$field)) {
                    try {
                        $this->lpaService->getLpaByPVCode(
                            $stateData->code,
                            $stateData->lastName,
                            $stateData->lpaUid,
                            $stateData->sentToDonor,
                            $stateData->attorneyName,
                            $stateData->dateOfBirth,
                            $stateData->noOfAttorneys
                        );
                        return $this->redirectToRoute($this->nextPage($stateData));
                    } catch (ApiException $apiEx) {
                        if ($apiEx->getCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
                            if ($apiEx->getMessage() === 'Not found') {
                                return new HtmlResponse(
                                    $this->renderer->render('viewer::paper-verification/lpa-not-found', [
                                        'donorName'     => $stateData->donorName,
                                        'lpaUid'        => $stateData->lpaUid,
                                        'sentToDonor'   => $stateData->sentToDonor,
                                        'dateOfBirth'   => $stateData->dateOfBirth?->format('Y-m-d'),
                                        'noOfAttorneys' => $stateData->noOfAttorneys,
                                        'attorneyName'  => $stateData->attorneyName,
                                        'en_message'    => $this->systemMessages['view/en'] ?? null,
                                        'cy_message'    => $this->systemMessages['view/cy'] ?? null,
                                    ])
                                );
                            }
                        }
                    }
                }
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
            'en_message'    => $this->systemMessages['view/en'] ?? null,
            'cy_message'    => $this->systemMessages['view/cy'] ?? null,
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
        /** @var PaperVerificationShareCode $state */
        return $state->sentToDonor === false
            ? 'pv.number-of-attorneys'
            : 'pv.provide-attorney-details';
    }
}
