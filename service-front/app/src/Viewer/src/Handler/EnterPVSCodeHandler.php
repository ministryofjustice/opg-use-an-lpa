<?php

declare(strict_types=1);

namespace Viewer\Handler;

use Common\Exception\ApiException;
use Common\Service\Features\FeatureEnabled;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\PVShareCode;
use Viewer\Form\ShareCode;
use Common\Service\Lpa\LpaService;

/**
 * @codeCoverageIgnore
 */
class EnterPVSCodeHandler extends AbstractPVSCodeHandler
{
    private ShareCode|PVShareCode $form;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
        private SystemMessageService $systemMessageService,
        private LpaService $lpaService,
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
            ? 'viewer::enter-code-pv'
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
            // TODO for now set values in session AND state. Once state is implemented on the check
            //      answers page for share codes then we can remove.
            $this->session->set('code', $this->form->getData()['lpa_code']);
            $this->session->set('surname', $this->form->getData()['donor_surname']);

            $this->state($request)->code     = $this->form->getData()['lpa_code'];
            $this->state($request)->lastName = $this->form->getData()['donor_surname'];

            if (
                isset($this->state($request)->code) && isset($this->state($request)->lastName)
            ) {
                try {
                    $lpa = $this->lpaService->getLpaByPVCode(
                        $this->state($request)->code,
                        $this->state($request)->lastName,
                        null,
                        null,
                        null,
                        null,
                        null
                    );

                    $this->state($request)->donorName = $lpa['donorName'] ?? null;
                    $this->state($request)->lpaType   = $lpa['type'] ?? null;

                    return $this->redirectToRoute($this->nextPage($this->state($request)));
                } catch (ApiException $apiEx) {
                    if ($apiEx->getCode() === StatusCodeInterface::STATUS_GONE) {
                        if ($apiEx->getMessage(
                            ) === 'LPA missing from upstream with verified paper verification code given') {
                            return new HtmlResponse($this->renderer->render('viewer::lpa-not-found-with-pvc'));
                        }
                        if ($apiEx->getMessage() === 'Paper verification code cancelled') {
                            return new HtmlResponse($this->renderer->render('viewer::lpa-cancelled-with-pvc'));
                        }
                        if ($apiEx->getMessage() === 'Paper verification code expired') {
                            return new HtmlResponse($this->renderer->render('viewer::lpa-expired-with-pvc'));
                        }
                    }
                }
            }
        }

        $template       = ($this->featureEnabled)('paper_verification')
            ? 'viewer::enter-code-pv'
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
        return ($this->featureEnabled)('paper_verification') ? 'pv.check-code' : 'check-code';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'home';
    }
}
