<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\Features\FeatureEnabled;
use Common\Service\SystemMessage\SystemMessageService;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\VerificationCodeReceiver;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationShareCode;

/**
 * @codeCoverageIgnore
 */
class PaperVerificationCodeSentToHandler extends AbstractPVSCodeHandler
{
    private VerificationCodeReceiver $form;

    /**
     * @var array{
     *     "view/en": string,
     *     "view/cy": string,
     * }
     */
    private array $systemMessages;

    private const TEMPLATE = 'viewer::paper-verification/verification-code-sent-to';

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
        $this->form           = new VerificationCodeReceiver($this->getCsrfGuard($request));
        $this->systemMessages = $this->systemMessageService->getMessages();

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $sentToDonor  = $this->state($request)->sentToDonor;
        $attorneyName = $this->state($request)->attorneyName;

        if ($sentToDonor !== null) {
            $this->form->setData(['verification_code_receiver' => $sentToDonor === false ? 'Attorney' : 'Donor']);
        }

        if ($attorneyName) {
            $this->form->setData(['attorney_name' => $attorneyName]);
        }

        $template = ($this->featureEnabled)('paper_verification')
            ? 'viewer::paper-verification/verification-code-sent-to'
            : 'viewer::enter-code';

        // TODO get donor name and add it to twig template
        return new HtmlResponse($this->renderer->render($template, [
            'donor_name'    => $this->state($request)->donorName ?? '(Donor name to be displayed here)',
            'sent_to_donor' => $sentToDonor ?? null,
            'attorneyName'  => $attorneyName ?? null,
            'form'          => $this->form->prepare(),
            'en_message'    => $this->systemMessages['view/en'] ?? null,
            'cy_message'    => $this->systemMessages['view/cy'] ?? null,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $storedSentToDonor = $this->state($request)->sentToDonor;
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $sentToDonor = $this->form->getData()['verification_code_receiver'] === 'Donor';

            if ($storedSentToDonor !== null && $storedSentToDonor !== $sentToDonor) {
                $this->state($request)->noOfAttorneys = null;
                $this->state($request)->attorneyName  = null;
                $this->state($request)->dateOfBirth   = null;
            }

            if (!$this->state($request)->sentToDonor = $sentToDonor) {
                $this->state($request)->attorneyName = $this->form->getData()['attorney_name'];
            }

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
            || $this->state($request)->lpaUid === null;
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
            $state->code !== null &&
            $state->attorneyName !== null;
    }

    /**
     * @inheritDoc
    */
    public function nextPage(WorkflowState $state): string
    {
        if ($this->hasFutureAnswersInState($state)) {
            return 'pv.check-answers';
        }

        return $state->sentToDonor === false ? 'pv.attorney-dob' : 'pv.donor-dob';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'home';
    }
}
