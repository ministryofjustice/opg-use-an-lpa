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
use Common\Middleware\Session\SessionTimeoutException;
use Common\Service\Lpa\LpaService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

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

    public function handlePost(ServerRequestInterface $request, Array $data): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;



        if ($this->form->isValid()) {
            // TODO UML-1161 / 1162 / 1163/ 1164

            // Does the user already have an activation key for the LPA
            // UML - 1164 -> already have an activation key for the LPA page

            // Check with Sirius if user provided data matches records
            //TO BE REMOVED and REPLACED with new logic created in LPA Service layer
            $lpaData = $this->lpaService->getLpaById($identity, '8e03498e-d24a-4770-affd-41225fe95aa4');
            $lpaData = $lpaData->lpa;

            //TO BE REMOVED. Added for UAT testing UI page.
            //$lpaData = null;

            // If LPA data found
            if (!is_null($lpaData)) {
                $expectedRegistrationDate = '2019-09-01';

                // Check if date LPA registered is not after Sep 2019
                // UML - 1163 -> Cannot send an activation key for that LPA
                if ($lpaData->getRegistrationDate() <= $expectedRegistrationDate) {
                    return $this->redirectToRoute('cannot-send-activation-key');
                }

                // If all user entered data matches in Sirius
                // UML- 1161  -> request to send letter and show activation key confirmation page
                return $this->redirectToRoute('send-activation-key-confirmation');
            } else {
                //  UML - 1162 -> LPA cannot be found
                return $this->redirectToRoute('cannot-find-lpa');
            }
        }
    }
}
