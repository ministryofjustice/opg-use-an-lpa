<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    Traits\CsrfGuard,
    Traits\User,
    UserAware,
    Traits\Session as SessionTrait};
use Actor\Form\CheckYourAnswers;
use Common\Exception\ApiException;
use Common\Middleware\Session\SessionTimeoutException;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Common\Service\Lpa\LpaService;
use Fig\Http\Message\StatusCodeInterface;

/**
 * Class CheckYourAnswersHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CheckYourAnswersHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

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
        LpaService $lpaService
    ) {
        parent::__construct($renderer, $urlHelper);

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
                return $this->handlePost($request, $this->data);
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

    public function handlePost(
        ServerRequestInterface $request,
        array $data
    ): ResponseInterface {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            // TODO UML-1216
            if (isset($data)) {
                try {
                    $response = $this->lpaService->checkLPAMatchAndRequestLetter(
                        $this->identity,
                        $data
                    );
                } catch (ApiException $apiEx) {
                    if ($apiEx->getCode() === StatusCodeInterface::STATUS_BAD_REQUEST) {
                        if ($apiEx->getMessage() === 'LPA not eligible') {
                            $this->getLogger()->info(
                                'LPA with reference number {uId} not eligible for activation key.',
                                [
                                    'uId' => $data['reference_number'],
                                ]
                            );
                            return new HtmlResponse($this->renderer->render('actor::cannot-send-activation-key'));
                        } else {
                            $this->getLogger()->info(
                                'LPA with reference number {uId} already has an activation key.',
                                [
                                    'uId' => $data['reference_number'],
                                ]
                            );
                            return new HtmlResponse($this->renderer->render('actor::already-have-activation-key'));
                        }
                    }
                    if ($apiEx->getCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
                        if ($apiEx->getMessage() === 'LPA not found') {
                            $this->getLogger()->info(
                                'LPA with reference number {uID} not found in Sirius',
                                [
                                    'uId' => $data['reference_number'],
                                ]
                            );
                            return new HtmlResponse($this->renderer->render('actor::cannot-find-lpa'));
                        }
                    }
                }

                //LPA check match and letter request sent
                $twoWeeksFromNowDate = (new DateTime())->modify('+2 week');
                return new HtmlResponse($this->renderer->render('actor::send-activation-key-confirmation', [
                    'date' => $twoWeeksFromNowDate,
                ]));
            }
        }
    }
}
