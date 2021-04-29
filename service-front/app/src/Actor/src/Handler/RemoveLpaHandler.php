<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\RemoveLpa;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RemoveLpaHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RemoveLpaHandler extends AbstractHandler implements UserAware
{
    use User;

    public const REMOVE_LPA_FLASH_MSG = 'remove_lpa_flash_msg';

    /** @var RemoveLpa */
    private $removeLpa;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        RemoveLpa $removeLpa,
        TranslatorInterface $translator
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->removeLpa = $removeLpa;
        $this->translator = $translator;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request)->getIdentity();
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $removedLpaData = ($this->removeLpa)($user, $actorLpaToken);

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $hw = $this->translator->translate('health and welfare', [], null, 'flashMessage');
        $pfa = $this->translator->translate('property and finance', [], null, 'flashMessage');

        //data to be used in flash message
        $donor = $removedLpaData['lpa']->getDonor()->getFirstname()
            . ' ' . $removedLpaData['lpa']->getDonor()->getSurname();
        $lpaType = $removedLpaData['lpa']->getCaseSubtype() === 'hw' ? $hw : $pfa;

        $message = $this->translator->translate(
            "You've removed %donor%'s %lpaType% LPA",
            [
                '%donor%' => $donor,
                '%lpaType%' => $lpaType
            ],
            null,
            'flashMessage'
        );
        $flash->flash(self::REMOVE_LPA_FLASH_MSG, $message);

        return $this->redirectToRoute('lpa.dashboard');
    }
}
