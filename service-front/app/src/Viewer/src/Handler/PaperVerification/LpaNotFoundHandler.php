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
use Viewer\Form\LpaCheck;
use Viewer\Handler\AbstractPVSCodeHandler;

/**
 * @codeCoverageIgnore
 */
class LpaNotFoundHandler extends AbstractPVSCodeHandler
{
    private LpaCheck $form;

    private const TEMPLATE = 'viewer::paper-verification/lpa-not-found';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form           = new LpaCheck($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $lpaUid        = $this->state($request)->lpaUid ?? 'M-1111-2222-3333';
        $sentToDonor   = $this->state($request)->sentToDonor ?? false;
        $dateOfBirth   = $this->state($request)->dateOfBirth?->format('j F Y') ?? '2 February 1994';
        $noOfAttorneys = $this->state($request)->noOfAttorneys ?? 2;
        $attorneyName  = $this->state($request)->attorneyName ?? 'Michael Clarke';
        $donorName     = $this->state($request)->donorName ?? 'Barbara Gilson';

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'lpaUid'        => $lpaUid,
            'sentToDonor'   => $sentToDonor,
            'dateOfBirth'   => $dateOfBirth,
            'noOfAttorneys' => $noOfAttorneys,
            'attorneyName'  => $attorneyName,
            'donorName'     => $donorName,
            'back'          => $this->lastPage($this->state($request)),
            'en_message'    => $this->systemMessages['view/en'] ?? null,
            'cy_message'    => $this->systemMessages['view/cy'] ?? null,
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
        return $this->state($request)->lastName === null
            || $this->state($request)->code === null
            || $this->state($request)->lpaUid === null
            || $this->state($request)->sentToDonor === null
            || $this->state($request)->sentToDonor === false;
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
