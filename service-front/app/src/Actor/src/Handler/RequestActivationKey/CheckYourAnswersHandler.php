<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Actor\Form\RequestActivationKey\CheckYourAnswers;
use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Actor\Form\RequestActivationKey\RequestContactDetails;
use Carbon\Carbon;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    LoggerAware,
    Traits\CsrfGuard,
    Traits\Logger,
    Traits\Session as SessionTrait,
    Traits\User,
    UserAware};
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Email\EmailClient;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Session\RemoveAccessForAllSessionValues;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use DateInterval;
use DateTime;

/**
 * Class CheckYourAnswersHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CheckYourAnswersHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;
    use Logger;

    private AddOlderLpa $addOlderLpa;
    private CheckYourAnswers $form;
    private RemoveAccessForAllSessionValues $removeAccessForAllSessionValues;
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private array $data;
    private ?string $identity;
    private FeatureEnabled $featureEnabled;

    /** @var LocalisedDate */
    private $localisedDate;

    /** @var EmailClient */
    private $emailClient;

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
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CheckYourAnswers($this->getCsrfGuard($request));
        $this->user = $this->getUser($request);
        $this->session = $this->getSession($request, 'session');
        $this->identity = (!is_null($this->user)) ? $this->user->getIdentity() : null;

        if (
            is_null($this->session)
            || is_null($this->session->get('opg_reference_number'))
            || is_null($this->session->get('first_names'))
            || is_null($this->session->get('last_name'))
            || is_null($this->session->get('dob')['day'])
            || is_null($this->session->get('dob')['month'])
            || is_null($this->session->get('dob')['year'])
            || is_null($this->session->get('postcode'))
        ) {
            throw new SessionTimeoutException();
        }

        $this->data = [
            'reference_number'  => (int) $this->session->get('opg_reference_number'),
            'first_names'       => $this->session->get('first_names'),
            'last_name'         => $this->session->get('last_name'),
            'dob'               =>
                Carbon::create(
                    $this->session->get('dob')['year'],
                    $this->session->get('dob')['month'],
                    $this->session->get('dob')['day']
                )->toImmutable(),
            'postcode'          => $this->session->get('postcode')
        ];

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render('actor::check-your-answers', [
            'user'  => $this->user,
            'form'  => $this->form,
            'data'  => $this->data
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->removeAccessForAllSessionValues->removePostLPAMatchSessionValues($this->session);

        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $result = $this->addOlderLpa->validate(
                $this->identity,
                $this->data['reference_number'],
                $this->data['first_names'],
                $this->data['last_name'],
                $this->data['dob'],
                $this->data['postcode']
            );

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::LPA_ALREADY_ADDED:
                    $lpaAddedData = $result->getData();
                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::lpa-already-added',
                            [
                                'user'          => $this->user,
                                'donor'         => $lpaAddedData->getDonor(),
                                'lpaType'       => $lpaAddedData->getCaseSubtype(),
                                'actorToken'    => $lpaAddedData->getLpaActorToken()
                            ]
                        )
                    );

                case OlderLpaApiResponse::NOT_ELIGIBLE:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-send-activation-key',
                        ['user'  => $this->user]
                    ));

                case OlderLpaApiResponse::HAS_ACTIVATION_KEY:
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request), true);
                    $form->setAttribute(
                        'action',
                        $this->urlHelper->generate('lpa.confirm-activation-key-generation')
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::already-have-activation-key',
                            [
                                'user'      => $this->user,
                                'dueDate'   => $result->getData()->getDueDate(),
                                'form'      => $form
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
                                'user'          => $this->user,
                                'dueDate'       => $activationKeyDueDate,
                                'form'          => $form
                            ]
                        )
                    );

                case OlderLpaApiResponse::DOES_NOT_MATCH:
                    if (($this->featureEnabled)('allow_older_lpas')) {
                        $form = new ActorRole($this->getCsrfGuard($request));
                        return $this->redirectToRoute('lpa.add.actor-role');
                    } else {
                        return new HtmlResponse($this->renderer->render(
                            'actor::cannot-find-lpa',
                            [
                                'user'  => $this->user,
                                'lpa_reference_number' => $this->data['reference_number']
                            ],
                        ));
                    }

                case OlderLpaApiResponse::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-find-lpa',
                        [
                            'user'  => $this->user,
                            'lpa_reference_number' => $this->data['reference_number']
                        ],
                    ));

                case OlderLpaApiResponse::FOUND:
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request));
                    $form->setAttribute(
                        'action',
                        $this->urlHelper->generate('lpa.confirm-activation-key-generation')
                    );

                    $lpaData = $result->getData();
                    $actor = is_null($lpaData->getAttorney()) ? $lpaData->getDonor() : $lpaData->getAttorney();

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::confirm-lpa-for-key-request',
                            [
                                'form'      => $form,
                                'user'      => $this->user,
                                'actor'     => $actor,
                                'actorRole' => (is_null($lpaData->getAttorney()) ? 'Donor' : 'Attorney'),
                                'donor'     => $lpaData->getDonor(),
                                'lpaType'   => $lpaData->getCaseSubtype()
                            ]
                        )
                    );
            }
        }
    }
}
