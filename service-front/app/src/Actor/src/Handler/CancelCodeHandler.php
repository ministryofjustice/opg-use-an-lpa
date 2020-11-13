<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CancelCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\LpaService, Lpa\ViewerCodeService};
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Acpr\I18n\TranslatorInterface;

/**
 * Class CancelCodeHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CancelCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use Session;
    use CsrfGuard;

    public const CANCEL_CODE_FLASH_MSG = 'cancel_code_flash_msg';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;
    /**
     * @var LpaService
     */
    private $lpaService;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService,
        UrlHelper $urlHelper,
        TranslatorInterface $translator
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->viewerCodeService = $viewerCodeService;
        $this->translator = $translator;
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
        $form = new CancelCode($this->getCsrfGuard($request));

        $user = $this->getUser($request);
        $identity = (!is_null($user)) ? $user->getIdentity() : null;

        $form->setData($request->getParsedBody());

        if ($form->isValid()) {
            $validated = $form->getData();

            $this->viewerCodeService->cancelShareCode(
                $identity,
                $validated['lpa_token'],
                $validated['viewer_code']
            );

            /** @var FlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            $formattedCode = str_split($validated['viewer_code'], 4);
            array_unshift($formattedCode, 'V');
            $formattedCode = implode("-", $formattedCode);

            $message = $this->translator->translate(
                "You cancelled the access code for %organisation%: %code%",
                [
                    '%code%' => $formattedCode,
                    '%organisation%' => $validated['organisation']
                ],
                null,
                'flashMessage'
            );

            $flash->flash(self::CANCEL_CODE_FLASH_MSG, $message);

            return new RedirectResponse(
                $this->urlHelper->generate(
                    'lpa.access-codes',
                    [],
                    ['lpa' => $validated['lpa_token']]
                )
            );
        }

        throw new InvalidRequestException('Invalid form submission');
    }
}
