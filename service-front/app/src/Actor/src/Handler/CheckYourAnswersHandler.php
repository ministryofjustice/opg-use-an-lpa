<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CheckYourAnswers;
use Actor\Form\CreateNewkey;
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
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\OlderLpaApiResponse;
use DateTime;
use IntlDateFormatter;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

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

    /** @var EmailClient */
    private $emailClient;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        AddOlderLpa $addOlderLpa,
        LoggerInterface $logger,
        EmailClient $emailClient
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
        $this->emailClient = $emailClient;
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
        $data =
        [
            'identity' => $this->identity,
            'reference_number' => $this->data['reference_number'],
            'first_names' => $this->data['first_names'],
            'last_name' => $this->data['last_name'],
            'dob' => $this->data['dob'],
            'postcode' => $this->data['postcode'],
        ];

          if ($this->form->isValid()) {
            $result = ($this->addOlderLpa)($data);

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::NOT_ELIGIBLE:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-send-activation-key',
                        ['user'  => $this->user]
                    ));
                case OlderLpaApiResponse::HAS_ACTIVATION_KEY:
                    $form = new CreateNewkey($this->getCsrfGuard($request));
                    $form->setAttribute('action', $this->urlHelper->generate('lpa.confirm-activation-key-generation'));
                    $data['force_activation_key'] = true;

                    $form->setData($data);

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::already-have-activation-key',
                            [
                                'user' => $this->user,
                                'donorName' => implode(' ', array_filter(array_map('trim',$result->getData()['donor_name']))), //GET A NAME ARRAY AND CONCATENATE HERE
                                'caseType' => $result->getData()['lpa_type'],
                                'form' => $form
                            ]
                        )
                    );

                case OlderLpaApiResponse::DOES_NOT_MATCH:
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
                        $this->localisedLetterExpectedDate($letterExpectedDate)
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

    /**
     * Uses duplicated code from the LpaExtension class to ensure that the date we send out in the
     * letters if correctly localised.
     *
     * Violation of DRY so TODO: https://opgtransform.atlassian.net/browse/UML-1370
     *
     * @param \DateTimeInterface $date
     *
     * @return string
     */
    private function localisedLetterExpectedDate(\DateTimeInterface $date): string
    {
        $formatter = IntlDateFormatter::create(
            \Locale::getDefault(),
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'Europe/London',
            IntlDateFormatter::GREGORIAN
        );

        return $formatter->format($date);
    }
}
