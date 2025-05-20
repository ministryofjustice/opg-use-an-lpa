<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\PVDateOfBirth;

/**
 * @codeCoverageIgnore
 */
class PVAttorneyDateOfBirthHandler extends AbstractPVSCodeHandler
{
    private PVDateOfBirth $form;

    public const TEMPLATE = 'viewer::attorney-dob';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form           = new PVDateOfBirth($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $attorneyName = $this->state($request)->attorneyName ?? 'Michael Clarke';

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
            $this->state($request)->code_receiver = $this->form->getData()['pv_date_of_birth'];
            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        $this->state($request)->code_receiver = $this->form->getData()['verification_code_receiver'];

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