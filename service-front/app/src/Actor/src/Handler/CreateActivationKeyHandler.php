<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateNewActivationKey;
use Carbon\Carbon;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\LpaService, Lpa\AddOlderLpa};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Email\EmailClient;
use IntlDateFormatter;
use DateTime;


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

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        AddOlderLpa $addOlderLpa,
        UrlHelper $urlHelper,
        EmailClient $emailClient)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;;
        $this->emailClient = $emailClient;
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
        $form = new CreateNewActivationKey($this->getCsrfGuard($request));
        $user = $this->getUser($request);

        $form->setData($request->getParsedBody());
        if ($form->isValid()) {
            $data = $form->getData();
            $data['identity'] = $user->getIdentity();
            $data['dob'] = (new DateTime($data['dob']));

            $result = ($this->addOlderLpa)($data);

            $letterExpectedDate = (new Carbon())->addWeeks(2);

            if ($result->getResponse() == OlderLpaApiResponse::SUCCESS) {
                $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                    $user->getDetails()['Email'],
                    $data['reference_number'],
                    strtoupper($data['postcode']),
                    $this->localisedLetterExpectedDate($letterExpectedDate)
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
            }
        }

        throw new InvalidRequestException('Invalid form');
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