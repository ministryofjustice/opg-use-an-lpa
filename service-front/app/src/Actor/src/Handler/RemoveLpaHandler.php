<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\RemoveLpa;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Actor\Form\RemoveLpa as RemoveLpaForm;
use Common\Service\Lpa\LpaService;

/**
 * Class RemoveLpaHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RemoveLpaHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;

    public const REMOVE_LPA_FLASH_MSG = 'remove_lpa_flash_msg';

    private RemoveLpa $removeLpa;
    private TranslatorInterface $translator;
    private RemoveLpaForm $form;
    private ?UserInterface $user;
    private LpaService $lpaService;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        RemoveLpa $removeLpa,
        TranslatorInterface $translator,
        LpaService $lpaService
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->removeLpa = $removeLpa;
        $this->translator = $translator;
        $this->lpaService = $lpaService;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);
        $this->form = new RemoveLpaForm($this->getCsrfGuard($request));

        switch ($request->getMethod()) {
            case 'POST':
                return $this->handlePost($request);
            default:
                return $this->handleGet($request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        $this->form->setData(['actor_lpa_token' => $actorLpaToken]);

        return new HtmlResponse($this->renderer->render('actor::confirm-remove-lpa', [
            'user' => $this->user,
            'actorToken' => $actorLpaToken,
            'form' => $this->form,
            'lpa' => $lpaData
        ]));
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $formData = $this->form->getData();
            $actorLpaToken = $formData['actor_lpa_token'];

            $removedLpaData = ($this->removeLpa)($this->user->getIdentity(), $actorLpaToken);

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
}
