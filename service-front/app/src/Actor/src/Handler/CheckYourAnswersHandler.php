<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CheckYourAnswers;
use Common\Exception\ApiException;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    LoggerAware,
    Traits\CsrfGuard,
    Traits\Logger,
    Traits\Session as SessionTrait,
    Traits\User,
    UserAware};
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Log\EventCodes;
use Common\Service\Lpa\LpaService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
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

    private CheckYourAnswers $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private array $data;
    private LpaService $lpaService;
    private ?string $identity;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LpaService $lpaService,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
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

        $dobString = sprintf(
            '%s-%s-%s',
            $this->session->get('dob')['year'],
            $this->session->get('dob')['month'],
            $this->session->get('dob')['day']
        );

        $this->data = [
            'reference_number'  => $this->session->get('opg_reference_number'),
            'first_names'       => $this->session->get('first_names'),
            'last_name'         => $this->session->get('last_name'),
            'dob'               => new DateTime($dobString),
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

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ApiException
     */
    public function handlePost(
        ServerRequestInterface $request
    ): ResponseInterface {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid() && isset($this->data)) {
            $response = $this->lpaService->checkLPAMatchAndRequestLetter(
                $this->identity,
                $this->data
            );

            switch ($response) {
                case EventCodes::DATA_DID_NOT_MATCH_LPA:
                case EventCodes::LPA_NOT_FOUND:
                    // LPA could not be found page
                    return new HtmlResponse($this->renderer->render('actor::cannot-find-lpa', [
                        'user' => $this->user,
                    ]));
                case EventCodes::LPA_NOT_ELIGIBLE:
                    // you cannot request an activation key page
                    return new HtmlResponse($this->renderer->render('actor::cannot-send-activation-key', [
                        'user' => $this->user,
                    ]));
                case EventCodes::LPA_HAS_ACTIVATION_KEY:
                    // where you can find your activation key page
                    return new HtmlResponse($this->renderer->render('actor::already-have-activation-key', [
                        'user' => $this->user,
                    ]));
                case EventCodes::LETTER_REQUESTED:
                    $twoWeeksAhead = new DateTime('+2 weeks');
                    return new HtmlResponse($this->renderer->render('actor::send-activation-key-confirmation', [
                        'user' => $this->user,
                        'date' => $twoWeeksAhead,
                    ]));
            }
        }
    }
}
