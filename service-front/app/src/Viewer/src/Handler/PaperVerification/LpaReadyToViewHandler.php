<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Workflow\WorkflowState;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\Organisation;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;

/**
 * @codeCoverageIgnore
 */
class LpaReadyToViewHandler extends AbstractPaperVerificationCodeHandler
{
    private Organisation $form;

    public const TEMPLATE = 'viewer::paper-verification/lpa-ready-to-view';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new Organisation($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
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
            $this->state($request)->organisation = $this->form->getData()['organisation'];
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
        return $this->state($request)->lastName === null
            || $this->state($request)->code === null
            || $this->state($request)->lpaUid === null
            || $this->state($request)->sentToDonor === null
            || $this->state($request)->attorneyName === null
            || $this->state($request)->noOfAttorneys === 0
            || $this->state($request)->noOfAttorneys === null;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return 'pv.view';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return 'pv.check-answers';
    }
}
