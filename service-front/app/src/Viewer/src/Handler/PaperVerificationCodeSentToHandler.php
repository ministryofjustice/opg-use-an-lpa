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
use Viewer\Form\VerificationCodeReceiver;

/**
 * @codeCoverageIgnore
 */
class PaperVerificationCodeSentToHandler extends AbstractPVSCodeHandler
{
    private VerificationCodeReceiver $form;

    private const TEMPLATE = 'viewer::paper-verification-check-code';

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
        $this->form           = new VerificationCodeReceiver($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        // TODO get donor name and add it to twig template
        $donorName = "(Donor name to be extracted and displayed here)";

        $template = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification-code-sent-to'
            : 'viewer::enter-code';

        return new HtmlResponse($this->renderer->render($template, [
            'donor_name' => $donorName,
            'form'       => $this->form->prepare(),
            'en_message' => $this->systemMessages['view/en'] ?? null,
            'cy_message' => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->session->set('code_receiver', $this->form->getData()['verification_code_receiver']);

            $this->state($request)->code_receiver = $this->form->getData()['verification_code_receiver'];

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
