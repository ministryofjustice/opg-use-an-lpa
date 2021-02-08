<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CheckYourAnswers;
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
use DateTime;
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

        $dobString = sprintf(
            '%s/%s/%s',
            $this->session->get('dob')['day'],
            $this->session->get('dob')['month'],
            $this->session->get('dob')['year']
        );

        $this->data = [
            'reference_number'  => $this->session->get('opg_reference_number'),
            'first_names'       => $this->session->get('first_names'),
            'last_name'         => $this->session->get('last_name'),
            'dob'               => $dobString,
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
                intval($this->data['reference_number']),
                $this->data['first_names'],
                $this->data['last_name'],
                DateTime::createFromFormat('d/m/Y', $this->data['dob']),
                $this->data['postcode'],
            );

            switch ($result) {
                case AddOlderLpa::NOT_ELIGIBLE:
                case AddOlderLpa::DOES_NOT_MATCH:
                    return new HtmlResponse($this->renderer->render('actor::cannot-send-activation-key'));
                case AddOlderLpa::HAS_ACTIVATION_KEY:
                    return new HtmlResponse($this->renderer->render('actor::already-have-activation-key'));
                case AddOlderLpa::NOT_FOUND:
                    return new HtmlResponse($this->renderer->render('actor::cannot-find-lpa'));
                case AddOlderLpa::SUCCESS:
                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::send-activation-key-confirmation',
                            [
                                'date' => (new DateTime())->modify('+2 week'),
                            ]
                        )
                    );
            }
        }
    }
}
