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
class LpaSummaryHandler extends AbstractPVSCodeHandler
{
    private CheckAnswers $form;

    public const TEMPLATE = 'viewer::paper-verification/lpa-summary';
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
        $this->systemMessages = $this->systemMessageService->getMessages();
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $stateData = $this->state($request);

        try {
            $lpaData = $this->lpaService->getLpaByPVCode(
                $stateData->code,
                $stateData->lastName,
                $stateData->lpaUid,
                $stateData->sentToDonor,
                $stateData->attorneyName,
                $stateData->dateOfBirth,
                $stateData->noOfAttorneys,
                $stateData->organisation
            );
        } catch (ApiException $ex) {
            $this->logger->error('Failed to fetch LPA data', ['error' => $ex->getMessage()]);
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
        // TODO remove on implementing view on api as a mocked response should be available
        $lpaData = [
            'lpaSource' => "LPASTORE",
            'lpa' => [
                'uId'   => '123456789012',
                'donor' => [
                    'uId' => '123456789012',
                    'dob' => '1980-01-01',
                ],
            ]
        ];
        //

        if (isset($lpaData['lpa']) && $lpaData['lpa'] !== null) {
           return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
               'lpa'        =>$lpaData['lpa'],
               'en_message' => $systemMessages['view/en'] ?? null,
               'cy_message' => $systemMessages['view/cy'] ?? null,
           ]));
       }
        return $this->redirectToRoute('home');
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        //POST shouldn’t happen, just reloading the same page
        return $this->redirectToRoute('pv.lpa-summary');
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

    public function nextPage(WorkflowState $state): string
    {
        return 'pv.lpa-summary';
    }

    public function lastPage(WorkflowState $state): string
    {
        /** @var PaperVerificationShareCode $state */
        return 'home';
    }
}
