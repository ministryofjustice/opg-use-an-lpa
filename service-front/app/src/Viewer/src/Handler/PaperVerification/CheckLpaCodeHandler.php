<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\Lpa\LpaService;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\LpaCheck;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationShareCode;

/**
 * @codeCoverageIgnore
 */
class CheckLpaCodeHandler extends AbstractPVSCodeHandler
{
    private LpaCheck $form;

    /**
     * @var array{
     *     "view/en": string,
     *     "view/cy": string,
     * }
     */
    private array $systemMessages;

    private const TEMPLATE = 'viewer::paper-verification/check-code';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private SystemMessageService $systemMessageService,
        private LpaService $lpaService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger, $lpaService);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form           = new LpaCheck($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $lpaUid = $this->state($request)->lpaUid;

        if ($lpaUid) {
            $this->form->setData(['lpa_reference' => $lpaUid]);
        }


        $lpa = $this->lpaService->getLpaByPVCode(
            $this->state($request)->code,
            $this->state($request)->lastName,
            null,
            null,
            null,
            null,
            null
        );

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'       => $this->form->prepare(),
            'donorName'  => $lpa->donorName,
            'lpaType'    => $lpa->type,
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->state($request)->lpaUid = $this->form->getData()['lpa_reference'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'       => $this->form->prepare(),
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
    }

    /**
     * @inheritDoc
     */
    public function hasFutureAnswersInState(PaperVerificationShareCode $state): bool
    {
        return
            $state->noOfAttorneys !== null &&
            $state->dateOfBirth !== null &&
            $state->lastName !== null &&
            $state->lpaUid !== null &&
            $state->sentToDonor !== null &&
            $state->attorneyName !== null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state)
            ? 'pv.check-answers'
            : 'pv.verification-code-sent-to';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'enter-code-pv';
    }
}
