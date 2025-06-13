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

/**
 * @codeCoverageIgnore
 */
class CheckAnswersHandler extends AbstractPVSCodeHandler
{
    public const TEMPLATE = 'viewer::paper-verification/check-answers';

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
        $lpaUid        = $this->state($request)->lpaUid ?? 'M-1111-2222-3333';
        $sentToDonor   = $this->state($request)->sentToDonor ?? true;
        $dateOfBirth   = $this->state($request)->dateOfBirth?->format('j F Y') ?? '2 February 1994';
        $noOfAttorneys = $this->state($request)->noOfAttorneys ?? 2;
        $attorneyName  = $this->state($request)->attorneyName ?? 'Michael Clarke';

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'lpaUid'        => $lpaUid,
            'sentToDonor'   => $sentToDonor,
            'dateOfBirth'   => $dateOfBirth,
            'noOfAttorneys' => $noOfAttorneys,
            'attorneyName'  => $attorneyName,
            'donorName'     => 'Barbara Gilson',
            'back'          => $this->lastPage($this->state($request)),
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
        return false;
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
        //needs changing when next page ready
        return 'home';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        //needs changing when next page ready
        return 'home';
    }
}
