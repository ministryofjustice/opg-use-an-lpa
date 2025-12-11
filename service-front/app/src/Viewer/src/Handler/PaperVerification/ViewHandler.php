<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\Lpa\PaperVerificationCodeService;
use Common\Service\Lpa\PaperVerificationCodeStatus;
use Common\Workflow\WorkflowState;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;

/**
 * @codeCoverageIgnore
 */
class ViewHandler extends AbstractPaperVerificationCodeHandler
{
    public const TEMPLATE = 'viewer::view-lpa-combined-lpa';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private PaperVerificationCodeService $paperVerificationCodeService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $stateData = $this->state($request);

        $response = $this->paperVerificationCodeService->view(
            $stateData->code->value,
            $stateData->lastName,
            $stateData->lpaUid,
            $stateData->sentToDonor,
            $stateData->attorneyName,
            $stateData->dateOfBirth,
            $stateData->noOfAttorneys,
            $stateData->organisation,
        );

        switch ($response->status) {
            case PaperVerificationCodeStatus::CANCELLED:
                return new HtmlResponse($this->renderer->render('viewer::paper-verification/code-cancelled'));

            case PaperVerificationCodeStatus::EXPIRED:
                return new HtmlResponse($this->renderer->render('viewer::paper-verification/code-expired'));

            case PaperVerificationCodeStatus::NOT_FOUND:
                return new HtmlResponse($this->renderer->render('viewer::paper-verification/could-not-find-lpa', [
                    'donor_last_name' => $stateData->lastName,
                    'lpa_access_code' => $stateData->code,
                ]));
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'lpa'  => $response->data,
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        throw new Exception('not implemented');
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        $stateData = $this->state($request);

        return $stateData->lastName === null
            || $stateData->code === null
            || $stateData->lpaUid === null
            || $stateData->sentToDonor === null
            || $stateData->attorneyName === null
            || $stateData->noOfAttorneys === 0
            || $stateData->noOfAttorneys === null
            || $stateData->organisation === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return 'home';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'pv.lpa-ready-to-view';
    }
}
