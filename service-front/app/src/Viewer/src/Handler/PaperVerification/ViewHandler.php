<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Exception\ApiException;
use Common\Service\Lpa\PaperVerificationCodeService;
use Common\Service\Lpa\PaperVerificationCodeStatus;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RectorPrefix202509\Nette\NotImplementedException;
use Viewer\Handler\AbstractPVSCodeHandler;

/**
 * @codeCoverageIgnore
 */
class ViewHandler extends AbstractPVSCodeHandler
{
    /**
     * @var array{
     *     "view/en": string,
     *     "view/cy": string,
     * }
     */
    private array $systemMessages;

    public const TEMPLATE = 'viewer::view-lpa-combined-lpa';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private SystemMessageService $systemMessageService,
        private PaperVerificationCodeService $paperVerificationCodeService,
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
            case PaperVerificationCodeStatus::OK:
                return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
                    'lpa'        => $response->data,
                    'back'       => $this->lastPage($this->state($request)),
                    'en_message' => $this->systemMessages['view/en'] ?? null,
                    'cy_message' => $this->systemMessages['view/cy'] ?? null,
                ]));

            case PaperVerificationCodeStatus::CANCELLED:
                return new HtmlResponse($this->renderer->render('viewer::paper-verification/check-code-cancelled'));

            case PaperVerificationCodeStatus::EXPIRED:
                return new HtmlResponse($this->renderer->render('viewer::paper-verification/check-code-expired'));
        }

        return new HtmlResponse($this->renderer->render('viewer::lpa-not-found-with-pvc', [
            'donor_last_name' => $stateData->lastName,
            'lpa_access_code' => $stateData->code,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        throw new NotImplementedException();
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
        return 'pv.enter-organisation-name';
    }
}
