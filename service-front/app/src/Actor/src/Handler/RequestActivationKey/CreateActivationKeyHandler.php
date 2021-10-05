<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\CreateNewActivationKey;
use Carbon\Carbon;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler,
    CsrfGuardAware,
    Traits\CsrfGuard,
    Traits\Session,
    Traits\User,
    UserAware};
use DateTime;
use Common\Service\{Lpa\AddOlderLpa};
use Common\Service\Email\EmailClient;
use Common\Service\Lpa\LocalisedDate;
use Common\Service\Lpa\OlderLpaApiResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class CreateActivationKeyHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CreateActivationKeyHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use Session;
    use CsrfGuard;

    /** @var EmailClient */
    private $emailClient;

    /** @var AddOlderLpa */
    private $addOlderLpa;

    /** @var LocalisedDate */
    private $localisedDate;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        AddOlderLpa $addOlderLpa,
        UrlHelper $urlHelper,
        EmailClient $emailClient,
        LocalisedDate $localisedDate
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;
        $this->emailClient = $emailClient;
        $this->localisedDate = $localisedDate;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);
        $form = new CreateNewActivationKey($this->getCsrfGuard($request));
        $identity = (!is_null($user)) ? $user->getIdentity() : null;
        $session = $this->getSession($request, 'session');

        $form->setData($request->getParsedBody());
        if (
            $form->isValid() &&
            $session->has('opg_reference_number') &&
            $session->has('first_names') &&
            $session->has('last_name') &&
            $session->has('dob') &&
            $session->has('postcode')
        ) {
            $result = $this->addOlderLpa->confirm(
                $identity,
                (int) $session->get('opg_reference_number'),
                $session->get('first_names'),
                $session->get('last_name'),
                Carbon::create(
                    $session->get('dob')['year'],
                    $session->get('dob')['month'],
                    $session->get('dob')['day']
                )->toImmutable(),
                $session->get('postcode'),
                $form->getData()['force_activation'] === 'yes'
            );

            switch ($result->getResponse()) {
                case OlderLpaApiResponse::SUCCESS:
                    $letterExpectedDate = (new Carbon())->addWeeks(2);

                    $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                        $user->getDetails()['Email'],
                        $session->get('opg_reference_number'),
                        strtoupper($session->get('postcode')),
                        ($this->localisedDate)($letterExpectedDate)
                    );

                    return new HtmlResponse(
                        $this->renderer->render(
                            'actor::send-activation-key-confirmation',
                            [
                                'date' => $letterExpectedDate,
                                'user' => $user,
                            ]
                        )
                    );
                case OlderLpaApiResponse::OLDER_LPA_NEEDS_CLEANSING:
                    $session->set('lpa_full_match_but_not_cleansed', true);
                    $session->set('actor_id', $result->getData()['actor_id']);

                    return $this->redirectToRoute('lpa.add.contact-details');
            }
        }

        throw new InvalidRequestException('Invalid form');
    }
}
