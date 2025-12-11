<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Entity\Code;
use Common\Service\Features\FeatureEnabled;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\PVShareCode;
use Viewer\Form\ShareCode;

/**
 * @codeCoverageIgnore
 */
class EnterCodeHandler extends AbstractPaperVerificationCodeHandler
{
    private ShareCode|PVShareCode $form;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
        private SystemMessageService $systemMessageService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (($this->featureEnabled)('paper_verification')) {
            $this->form = new PVShareCode($this->getCsrfGuard($request));
        } else {
            $this->form = new ShareCode($this->getCsrfGuard($request));
        }

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        // reset the state on a new visit.
        $this->state($request)->reset();

        $template       = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification/enter-code'
            : 'viewer::enter-code';
        $systemMessages = $this->systemMessageService->getMessages();

        return new HtmlResponse($this->renderer->render($template, [
            'form'       => $this->form->prepare(),
            'en_message' => $systemMessages['view/en'] ?? null,
            'cy_message' => $systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $this->state($request)->code     = new Code($this->form->getData()['lpa_code']);
            $this->state($request)->lastName = $this->form->getData()['donor_surname'];

            // to allow the non-paper verification code CheckCodeHandler to work
            $this->session->set('code', $this->state($request)->code->value);
            $this->session->set('surname', $this->state($request)->lastName);

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        $template       = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification/enter-code'
            : 'viewer::enter-code';
        $systemMessages = $this->systemMessageService->getMessages();

        return new HtmlResponse($this->renderer->render($template, [
            'form'       => $this->form->prepare(),
            'en_message' => $systemMessages['view/en'] ?? null,
            'cy_message' => $systemMessages['view/cy'] ?? null,
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
        if (($this->featureEnabled)('paper_verification') && $state->code->isPaperVerificationCode()) {
            return 'pv.found-lpa';
        }

        return 'check-code';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'home';
    }
}
