<?php

declare(strict_types=1);

namespace Viewer\Handler\PaperVerification;

use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Viewer\Form\DateOfBirth;
use Viewer\Handler\AbstractPaperVerificationCodeHandler;
use Viewer\Workflow\PaperVerificationCode;

/**
 * @codeCoverageIgnore
 */
class DonorDateOfBirthHandler extends AbstractPaperVerificationCodeHandler
{
    private DateOfBirth $form;

    public const TEMPLATE = 'viewer::paper-verification/date-of-birth';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new DateOfBirth($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $dob = $this->state($request)->dateOfBirth;

        if ($dob) {
            $this->form->setData([
                 'dob' => [
                     'day'   => $dob->format('d'),
                     'month' => $dob->format('m'),
                     'year'  => $dob->format('Y'),
                 ],
             ]);
        }

        return new HtmlResponse($this->renderer->render(self::TEMPLATE, [
            'form' => $this->form->prepare(),
            'name' => $this->state($request)->donorName,
            'back' => $this->lastPage($this->state($request)),
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
            'name' => $this->state($request)->donorName,
            'back' => $this->lastPage($this->state($request)),
        ]));
    }

    /**
     * @inheritDoc
     */
    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->sentToDonor !== true;
    }

    /**
     * @inheritDoc
     */
    public function nextPage(WorkflowState $state): string
    {
        return $this->shouldCheckAnswers($state) ? 'pv.check-answers' : 'pv.attorney-details';
    }

    /**
     * @inheritDoc
     */
    public function lastPage(WorkflowState $state): string
    {
        return $this->shouldCheckAnswers($state) ? 'pv.check-answers' : 'pv.code-sent-to';
    }
}
