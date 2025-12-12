<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Service\Lpa\PaperVerificationCodeService;
use Common\Service\Lpa\PaperVerificationCodeStatus;
use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\LpaReferenceNumber;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;

/**
 * @codeCoverageIgnore
 */
class FoundLpaHandler extends AbstractPaperVerificationCodeHandler
{
    private LpaReferenceNumber $form;

    private const TEMPLATE = 'viewer::paper-verification/found-lpa';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private PaperVerificationCodeService $paperVerificationCodeService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new LpaReferenceNumber($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $lpaUid = $this->state($request)->lpaUid;

        if ($lpaUid) {
            $this->form->setData(['lpa_reference' => $lpaUid]);
        }

        $code     = $this->state($request)->code->value;
        $lastName = $this->state($request)->lastName;

        if (isset($code)) {
            $result = $this->paperVerificationCodeService->usable($code, $lastName);

            switch ($result->status) {
                case PaperVerificationCodeStatus::CANCELLED:
                    return new HtmlResponse($this->renderer->render('viewer::paper-verification/code-cancelled'));

                case PaperVerificationCodeStatus::EXPIRED:
                    return new HtmlResponse($this->renderer->render('viewer::paper-verification/code-expired'));

                case PaperVerificationCodeStatus::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render('viewer::paper-verification/could-not-find-lpa', [
                        'donorLastName'         => $lastName,
                        'paperVerificationCode' => $code,
                    ]));
            }
        }

        $this->state($request)->donorName = $result->data->donorName;
        $this->state($request)->lpaType   = $result->data->lpaType;

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'      => $this->form->prepare(),
            'donorName' => $this->state($request)->donorName,
            'lpaType'   => $this->state($request)->lpaType,
            'back'      => $this->lastPage($this->state($request)),
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
            'form'      => $this->form->prepare(),
            'donorName' => $this->state($request)->donorName,
            'lpaType'   => $this->state($request)->lpaType,
            'back'      => $this->lastPage($this->state($request)),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->code === null
            && $this->state($request)->lastName === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return $this->shouldCheckAnswers($state) ? 'pv.check-answers' : 'pv.code-sent-to';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return $this->shouldCheckAnswers($state) ? 'pv.check-answers' : 'home';
    }
}
