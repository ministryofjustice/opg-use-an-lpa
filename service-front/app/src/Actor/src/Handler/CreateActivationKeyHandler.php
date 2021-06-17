<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateNewActivationKey;
use Carbon\Carbon;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\AddOlderLpa};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Email\EmailClient;
use IntlDateFormatter;
use DateTime;
use Common\Service\Lpa\FormatDate;


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

    /** @var FormatDate */
    private $formatDate;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        AddOlderLpa $addOlderLpa,
        UrlHelper $urlHelper,
        EmailClient $emailClient,
        FormatDate $formatDate
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->addOlderLpa = $addOlderLpa;;
        $this->emailClient = $emailClient;
        $this->formatDate = $formatDate;
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
        $forceActivationKey = true;

        $form->setData($request->getParsedBody());

        if ($form->isValid()) {
            $data = $form->getData();

            $result = ($this->addOlderLpa)(
                $user->getIdentity(),
                (int)$data['reference_number'],
                $data['first_names'],
                $data['last_name'],
                new DateTime($data['dob']),
                $data['postcode'],
                $forceActivationKey
            );

            $letterExpectedDate = (new Carbon())->addWeeks(2);

            if ($result->getResponse() == OlderLpaApiResponse::SUCCESS) {
                $this->emailClient->sendActivationKeyRequestConfirmationEmail(
                    $user->getDetails()['Email'],
                    $data['reference_number'],
                    strtoupper($data['postcode']),
                    ($this->formatDate)($letterExpectedDate)
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
}