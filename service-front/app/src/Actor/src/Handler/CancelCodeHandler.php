<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\CancelCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\LpaService, Lpa\ViewerCodeService};
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 */
class CancelCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use CsrfGuard;
    use Session;
    use User;

    public const CANCEL_CODE_FLASH_MSG = 'cancel_code_flash_msg';

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        private LpaService $lpaService,
        private ViewerCodeService $viewerCodeService,
        UrlHelper $urlHelper,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * Handles a request and produces a response
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new CancelCode($this->getCsrfGuard($request));

        $user     = $this->getUser($request);
        $identity = !is_null($user) ? $user->getIdentity() : null;

        $form->setData($request->getParsedBody());

        if ($form->isValid()) {
            $validated = $form->getData();

            $this->viewerCodeService->cancelShareCode(
                $identity,
                $validated['lpa_token'],
                $validated['viewer_code']
            );

            /**
 * @var FlashMessagesInterface $flash
*/
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            $formattedCode = str_split($validated['viewer_code'], 4);
            array_unshift($formattedCode, 'V');
            $formattedCode = implode('-', $formattedCode);

            $message = $this->translator->translate(
                'You cancelled the access code for %organisation%: %code%',
                [
                    '%code%'         => $formattedCode,
                    '%organisation%' => $validated['organisation'],
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
