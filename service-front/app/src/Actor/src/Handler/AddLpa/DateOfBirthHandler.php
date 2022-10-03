<?php

declare(strict_types=1);

namespace Actor\Handler\AddLpa;

use Actor\Form\AddLpa\DateOfBirth;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class DateOfBirthHandler extends AbstractAddLpaHandler
{
    private DateOfBirth $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new DateOfBirth($this->getCsrfGuard($request));

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $data = [];
        if (($dob = $this->state($request)->dateOfBirth) !== null) {
            $data['dob'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];
        }

        $this->form->setData($data);

        return new HtmlResponse(
            $this->renderer->render(
                'actor::add-lpa/date-of-birth',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request)),
                ]
            )
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->dateOfBirth = (new DateTimeImmutable())->setDate(
                (int)$postData['dob']['year'],
                (int)$postData['dob']['month'],
                (int)$postData['dob']['day']
            );

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::add-lpa/date-of-birth',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request)),
                ]
            )
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->activationKey === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.add-by-key.lpa-reference-number';
    }

    public function lastPage(WorkflowState $state): string
    {
        return 'lpa.add-by-key';
    }
}
