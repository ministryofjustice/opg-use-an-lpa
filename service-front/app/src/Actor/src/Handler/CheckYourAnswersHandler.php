<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CheckYourAnswers;
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
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\OlderLpaApiResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
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
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private array $data;
    private ?string $identity;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        AddOlderLpa $addOlderLpa,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
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
                $this->data['postcode'],
            );

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::NOT_ELIGIBLE:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-send-activation-key',
                        ['user'  => $this->user]
                    ));
                case OlderLpaApiResponse::HAS_ACTIVATION_KEY:
                    $createdDate = DateTime::createFromFormat('Y-m-d', $result->getData()['activation_key_created']);
                    if ((int) $createdDate->diff(new DateTime(), true)->format('%a') <= 14) {
                        return new HtmlResponse($this->renderer->render(
                            'actor::already-requested-activation-key',
                            [
                                'user'  => $this->user,
                                'arrival_date' => DateTime::createFromFormat(
                                    'Y-m-d',
                                    $result->getData()['activation_key_created']
                                )->modify('+2 weeks')
                            ]
                        ));
                    }
                    return new HtmlResponse($this->renderer->render(
                        'actor::already-have-activation-key',
                        ['user'  => $this->user]
                    ));
                case OlderLpaApiResponse::DOES_NOT_MATCH:
                case OlderLpaApiResponse::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render(
                        'actor::cannot-find-lpa',
                        ['user'  => $this->user]
                    ));
                case OlderLpaApiResponse::SUCCESS:
                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::send-activation-key-confirmation',
                            [
                                'date' => (new Carbon())->addWeeks(2),
                                'user'  => $this->user
                            ]
                        )
                    );
            }
        }
    }
}
