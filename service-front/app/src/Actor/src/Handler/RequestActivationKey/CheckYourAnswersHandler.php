<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\ActorRole;
use Actor\Form\RequestActivationKey\CheckYourAnswers;
use Actor\Form\RequestActivationKey\CreateNewActivationKey;
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
use Common\Service\Lpa\OlderLpaApiResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use Common\Service\Lpa\LocalisedDate;

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
        FeatureEnabled $featureEnabled
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
        $this->emailClient = $emailClient;
        $this->localisedDate = $localisedDate;
        $this->featureEnabled = $featureEnabled;
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
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $result = ($this->addOlderLpa)(
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
                    $form = new CreateNewActivationKey($this->getCsrfGuard($request));
                    $form->setAttribute('action', $this->urlHelper->generate('lpa.confirm-activation-key-generation'));

                    $form->setData($this->data);

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::already-have-activation-key',
                            [
                                'user'      => $this->user,
                                'donor'     => $result->getData()->getDonor(),
                                'lpaType'   => $result->getData()->getCaseSubtype(),
                                'form'      => $form
                            ]
                        )
                    );

                case OlderLpaApiResponse::DOES_NOT_MATCH:
                    if (($this->featureEnabled)('allow_older_lpas')) {
                        $form = new ActorRole($this->getCsrfGuard($request));
                        $form->setAttribute('action', $this->urlHelper->generate('lpa.add.actor-role'));
                        return new HtmlResponse($this->renderer->render(
                            'actor::request-activation-key/actor-role',
                            [
                                'user'  => $this->user,
                                'form'  => $form
                            ]
                        ));
                    } else {
                        return new HtmlResponse($this->renderer->render(
                            'actor::cannot-find-lpa',
                            ['user'  => $this->user]
                        ));
                    }
                case OlderLpaApiResponse::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-find-lpa',
                        ['user'  => $this->user]
                    ));
                case OlderLpaApiResponse::SUCCESS:
                    $letterExpectedDate = (new Carbon())->addWeeks(2);

                    $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                        $this->user->getDetails()['Email'],
                        (string)$this->data['reference_number'],
                        $this->data['postcode'],
                        ($this->localisedDate)($letterExpectedDate)
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::send-activation-key-confirmation',
                            [
                                'date' => $letterExpectedDate,
                                'user'  => $this->user
                            ]
                        )
                    );
            }
        }
    }
}
