<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CheckYourAnswers;
use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Actor\Workflow\RequestActivationKey;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    LoggerAware,
    Traits\CsrfGuard,
    Traits\Logger,
    Traits\Session as SessionTrait,
    Traits\User,
    UserAware};
use Common\Service\Email\EmailClient;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Session\RemoveAccessForAllSessionValues;
use Common\Workflow\State;
use Common\Workflow\StateNotInitialisedException;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class CheckYourAnswersHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CheckYourAnswersHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware, WorkflowStep
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;
    use State;

    private CheckYourAnswers $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;

    private AddOlderLpa $addOlderLpa;
    private EmailClient $emailClient;
    private LocalisedDate $localisedDate;
    private FeatureEnabled $featureEnabled;
    private RemoveAccessForAllSessionValues $removeAccessForAllSessionValues;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        AddOlderLpa $addOlderLpa,
        LoggerInterface $logger,
        EmailClient $emailClient,
        LocalisedDate $localisedDate,
        FeatureEnabled $featureEnabled,
        RemoveAccessForAllSessionValues $removeAccessForAllSessionValues
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
        $this->emailClient = $emailClient;
        $this->localisedDate = $localisedDate;
        $this->featureEnabled = $featureEnabled;
        $this->removeAccessForAllSessionValues = $removeAccessForAllSessionValues;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckYourAnswers($this->getCsrfGuard($request));

        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');

        if ($this->isMissingPrerequisite($request)) {
            return $this->redirectToRoute('lpa.add-by-paper');
        }

        return match ($request->getMethod()) {
            'POST'  => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $state = $this->state($request);
        $data = [
            'reference_number' => $state->referenceNumber,
            'first_names'      => $state->firstNames,
            'last_name'        => $state->lastName,
            'dob'              => $state->dob,
            'postcode'         => $state->postcode
        ];

        return new HtmlResponse($this->renderer->render(
            'actor::check-your-answers',
            [
                'user' => $this->user,
                'form' => $this->form,
                'data' => $data
            ]
        ));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws StateNotInitialisedException
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->removeAccessForAllSessionValues->removePostLPAMatchSessionValues($this->session);

        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $state = $this->state($request);

            $result = $this->addOlderLpa->validate(
                $this->user?->getIdentity(), // PHP8 nullsafe operator
                $state->referenceNumber,
                $state->firstNames,
                $state->lastName,
                $state->dob,
                $state->postcode
            );

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::LPA_ALREADY_ADDED:
                    $lpaAddedData = $result->getData();
                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::lpa-already-added',
                            [
                                'user'       => $this->user,
                                'donor'      => $lpaAddedData->getDonor(),
                                'lpaType'    => $lpaAddedData->getCaseSubtype(),
                                'actorToken' => $lpaAddedData->getLpaActorToken()
                            ]
                        )
                    );

                case OlderLpaApiResponse::NOT_ELIGIBLE:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-send-activation-key',
                        [ 'user' => $this->user ]
                    ));

                case OlderLpaApiResponse::HAS_ACTIVATION_KEY:
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request), true);
                    $form->setAttribute(
                        'action',
                        $this->urlHelper->generate('lpa.confirm-activation-key-generation')
                    );

                    $activationKeyDueDate = date(
                        'Y-m-d',
                        strtotime(($result->getData()->getDueDate()))
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::already-have-activation-key',
                            [
                                'user'    => $this->user,
                                'dueDate' => $activationKeyDueDate,
                                'form'    => $form
                            ]
                        )
                    );

                case OlderLpaApiResponse::KEY_ALREADY_REQUESTED:
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request), true);
                    $form->setAttribute(
                        'action',
                        $this->urlHelper->generate('lpa.confirm-activation-key-generation')
                    );

                    $activationKeyDueDate = date(
                        'Y-m-d',
                        strtotime(($result->getData()->getDueDate()))
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::already-requested-activation-key',
                            [
                                'user'    => $this->user,
                                'dueDate' => $activationKeyDueDate,
                                'form'    => $form
                            ]
                        )
                    );

                case OlderLpaApiResponse::DOES_NOT_MATCH:
                    if (($this->featureEnabled)('allow_older_lpas')) {
                        return $this->redirectToRoute('lpa.add.actor-role');
                    } else {
                        return new HtmlResponse($this->renderer->render(
                            'actor::cannot-find-lpa',
                            [
                                'user'                 => $this->user,
                                'lpa_reference_number' => $state->referenceNumber,
                                'first_name'           => $state->firstNames,
                                'last_name'            => $state->lastName,
                                'dob'                  => $state->dob,
                                'postcode'             => $state->postcode
                            ],
                        ));
                    }

                case OlderLpaApiResponse::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-find-lpa',
                        [
                            'user'                 => $this->user,
                            'lpa_reference_number' => $state->referenceNumber,
                            'first_name'           => $state->firstNames,
                            'last_name'            => $state->lastName,
                            'dob'                  => $state->dob,
                            'postcode'             => $state->postcode
                        ],
                    ));

                case OlderLpaApiResponse::FOUND:
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request));
                    $form->setAttribute(
                        'action',
                        $this->urlHelper->generate('lpa.confirm-activation-key-generation')
                    );

                    $lpaData = $result->getData();
                    $actor = $lpaData->getAttorney() === null ? $lpaData->getDonor() : $lpaData->getAttorney();

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::confirm-lpa-for-key-request',
                            [
                                'form'      => $form,
                                'user'      => $this->user,
                                'actor'     => $actor,
                                'actorRole' => $lpaData->getAttorney() === null ? 'Donor' : 'Attorney',
                                'donor'     => $lpaData->getDonor(),
                                'lpaType'   => $lpaData->getCaseSubtype()
                            ]
                        )
                    );
            }

            $this->getLogger()->alert(
                'No valid older LPA addition response was returned from our API in ' . __METHOD__
            );
            throw new RuntimeException('No valid response was returned from our API');
        }

        $this->getLogger()->alert('Invalid CSRF when submitting to ' . __METHOD__);
        throw new InvalidRequestException('Invalid CSRF when submitting form');
    }

    public function state(ServerRequestInterface $request): RequestActivationKey
    {
        return $this->loadState($request, RequestActivationKey::class);
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return $this->state($request)->referenceNumber === null
            || $this->state($request)->firstNames === null
            || $this->state($request)->lastName === null
            || $this->state($request)->dob === null
            || $this->state($request)->postcode === null;
    }

    public function nextPage(WorkflowState $state): string
    {
        return 'lpa.add-by-paper';
    }

    public function lastPage(WorkflowState $state): string
    {
        return 'lpa.postcode';
    }
}
