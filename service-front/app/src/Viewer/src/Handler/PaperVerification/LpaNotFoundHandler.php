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
use Viewer\Form\LpaCheck;
use Viewer\Handler\AbstractPVSCodeHandler;

/**
 * @codeCoverageIgnore
 */
class LpaNotFoundHandler extends AbstractPVSCodeHandler
{
    private LpaCheck $form;

    private const TEMPLATE = 'viewer::paper-verification/cannot-show-lpa';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new LpaCheck($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {

        $stateData = $this->state($request);

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'lpaUid'        => $stateData->lpaUid,
            'sentToDonor'   => $stateData->sentToDonor,
            'dateOfBirth'   => $stateData->dateOfBirth,
            'noOfAttorneys' => $stateData->noOfAttorneys,
            'attorneyName'  => $stateData->attorneyName,
            'donorName'     => $stateData->donorName,
            'back'          => $this->lastPage($this->state($request)),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form' => $this->form->prepare(),
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
