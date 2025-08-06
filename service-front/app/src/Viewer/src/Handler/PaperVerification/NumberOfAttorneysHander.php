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
use Psr\Log\LoggerInterface;
use Viewer\Form\NumberOfAttorneys;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationShareCode;

/**
 * @codeCoverageIgnore
 */
class NumberOfAttorneysHander extends AbstractPVSCodeHandler
{
    private NumberOfAttorneys $form;
    /**
     * @var array{
     *     "view/en": string,
     *     "view/cy": string,
     * }
     */
    private array $systemMessages;
    public const TEMPLATE = 'viewer::paper-verification/number-of-attorneys';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form           = new NumberOfAttorneys($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $attorneyName = $this->state($request)->attorneyName ?? 'Michael Clarke';
        $noOfAttorneys = $this->state($request)->noOfAttorneys;

        if ($noOfAttorneys) {
            $this->form->setData(['no_of_attorneys' => $noOfAttorneys]);
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'         => $this->form->prepare(),
            'attorneyName' => $attorneyName,
            'back'         => $this->lastPage($this->state($request)),
            'en_message'   => $this->systemMessages['view/en'] ?? null,
            'cy_message'   => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->state($request)->noOfAttorneys = $this->form->getData()['no_of_attorneys'];
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
        return 'pv.check-answers';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state)
            ? 'pv.check-answers'
            : 'pv.attorney-dob';
    }
}
