<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Service\Features\FeatureEnabled;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\LpaCheck;

/**
 * @codeCoverageIgnore
 */
class PaperVerificationLpaCheckHandler extends AbstractPVSCodeHandler
{
    private LpaCheck $form;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        private FeatureEnabled $featureEnabled,
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
        $this->state($request)->reset();

        $template = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification-lpa-check'
            : 'viewer::enter-code';

        return new HtmlResponse($this->renderer->render($template, [
            'form'       => $this->form->prepare(),
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->session->set('lpa_check', $this->form->getData()['lpa_check']);
            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        $template = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification-lpa-check'
            : 'viewer::enter-code';

        return new HtmlResponse($this->renderer->render($template, [
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
