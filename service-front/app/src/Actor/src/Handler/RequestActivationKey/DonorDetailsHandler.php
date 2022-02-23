<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\DonorDetails;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session as SessionTrait;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Workflow\State;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class DonorDetailsHandler
 * @package Actor\RequestActivationKey\Handler
 * @codeCoverageIgnore
 */
class DonorDetailsHandler extends AbstractCleansingDetailsHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use State;

    private DonorDetails $form;

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new DonorDetails($this->getCsrfGuard($request));
        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'donor_first_names' => $this->state($request)->donorFirstNames,
            'donor_last_name' => $this->state($request)->donorLastName
        ];

        if (($dob = $this->state($request)->donorDob) !== null) {
            $data['donor_dob'] = [
                'day' => $dob->format('d'),
                'month' => $dob->format('m'),
                'year' => $dob->format('Y'),
            ];
        }

        $this->form->setData($data);

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/donor-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            $this->state($request)->donorFirstNames = $postData['donor_first_names'];
            $this->state($request)->donorLastName = $postData['donor_last_name'];
            $this->state($request)->donorDob = (new DateTimeImmutable())->setDate(
                (int) $postData['donor_dob']['year'],
                (int) $postData['donor_dob']['month'],
                (int) $postData['donor_dob']['day']
            );

            $nextPageName = $this->nextPage($this->state($request));
            return $this->redirectToRoute($nextPageName);
        }

        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/donor-details',
            [
                'user' => $this->user,
                'form' => $this->form->prepare(),
                'back' => $this->lastPage($this->state($request))
            ]
        ));
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return parent::isMissingPrerequisite($request)
            || $this->state($request)->getActorRole() === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state) ? 'lpa.add.check-details-and-consent' : 'lpa.add.contact-details';
    }

    public function lastPage(WorkflowState $state): string
    {
        return $this->hasFutureAnswersInState($state) ? 'lpa.add.check-details-and-consent' : 'lpa.add.actor-role';
    }
}
