<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationShareCode;

/**
 * @codeCoverageIgnore
 */
class CheckAnswersHandler extends AbstractPVSCodeHandler
{
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
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $stateData = $this->state($request);

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'lpaUid'        => $stateData->lpaUid,
            'sentToDonor'   => $stateData->sentToDonor,
            'dateOfBirth'   => $stateData->dateOfBirth,
            'noOfAttorneys' => $stateData->noOfAttorneys,
            'attorneyName'  => $stateData->attorneyName,
            'donorName'     => 'Barbara Gilson',
            'back'          => $this->lastPage($stateData),
            'en_message'    => $this->systemMessages['view/en'] ?? null,
            'cy_message'    => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
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
        return 'enter-organisation-name';
    }

    /**
     * @return string The route name of the previous page in the workflow
     */
    public function lastPage(WorkflowState $state): string
    {
        /** @var PaperVerificationShareCode $state */
        return $state->sentToDonor === false ? 'pv.number-of-attorneys' : 'pv.provide-attorney-details';
    }
}
