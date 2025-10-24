<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viewer\Form\PVDateOfBirth;
use Viewer\Handler\AbstractPVSCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class AttorneyDateOfBirthHandler extends AbstractPVSCodeHandler
{
    private PVDateOfBirth $form;

    private const TEMPLATE = 'viewer::paper-verification/attorney-dob';

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new PVDateOfBirth($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $dob = $this->state($request)->dateOfBirth;

        if ($dob) {
            $this->form->setData(
                [
                    'dob' => [
                        'day'   => $dob->format('d'),
                        'month' => $dob->format('m'),
                        'year'  => $dob->format('Y'),
                    ],
                ]
            );
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form'         => $this->form->prepare(),
            'attorneyName' => $this->state($request)->attorneyName,
            'back'         => $this->lastPage($this->state($request)),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->dateOfBirth = (new DateTimeImmutable())->setDate(
                (int) $postData['dob']['year'],
                (int) $postData['dob']['month'],
                (int) $postData['dob']['day']
            );
            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

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
            || $this->state($request)->sentToDonor === null;
    }

    /**
     * @inheritDoc
     */
    public function hasFutureAnswersInState(PaperVerificationCode $state): bool
    {
        return
            $state->noOfAttorneys !== null &&
            $state->sentToDonor !== null &&
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
        return $this->hasFutureAnswersInState($state)
            ? 'pv.check-answers'
            : 'pv.number-of-attorneys';
    }

    /**
     * @inheritDoc
    */
    public function lastPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state)
            ? 'pv.check-answers'
            : 'pv.verification-code-sent-to';
    }
}
