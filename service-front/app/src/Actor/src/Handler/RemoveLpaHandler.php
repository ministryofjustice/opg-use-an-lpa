<?php

declare(strict_types=1);

namespace Actor\Handler;

use Acpr\I18n\TranslatorInterface;
use Actor\Form\RemoveLpa as RemoveLpaForm;
use App\Exception\BadRequestException;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\RemoveLpa;
use Common\Service\Lpa\LpaTypeResolver;
use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class RemoveLpaHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use CsrfGuard;
    use User;

    public const REMOVE_LPA_FLASH_MSG = 'remove_lpa_flash_msg';

    private RemoveLpaForm $form;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private RemoveLpa $removeLpa,
        private LpaTypeResolver $lpaTypeResolver,
        private TranslatorInterface $translator,
        private LpaService $lpaService,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);
        $this->form = new RemoveLpaForm($this->getCsrfGuard($request));

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $identity = $this->user?->getIdentity();

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        $this->form->setData(['actor_lpa_token' => $actorLpaToken]);

        if (!empty($lpaData['lpa'])) {
            $label = $this->lpaTypeResolver->resolveLabel(
                $lpaData['lpa']->getCaseSubtype(),
                $lpaData['lpa']->getUId()
            );
        } else {
            throw new BadRequestException('Lpa to remove cannot be found');
        }
        $lpaType = $this->translator->translate($label);

        return new HtmlResponse($this->renderer->render('actor::confirm-remove-lpa', [
            'user'       => $this->user,
            'actorToken' => $actorLpaToken,
            'form'       => $this->form,
            'lpa'        => $lpaData['lpa'],
            'lpaType'    => $lpaType,
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $formData      = $this->form->getData();
            $actorLpaToken = $formData['actor_lpa_token'];

            $removedLpaData = ($this->removeLpa)($this->user->getIdentity(), $actorLpaToken);

            /** @var FlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            //data to be used in flash message
            $donor = $removedLpaData['lpa']['donor']['firstnames'] . ' ' . $removedLpaData['lpa']['donor']['surname'];

            $label   = $this->lpaTypeResolver->resolveLabel(
                $removedLpaData['lpa']['caseSubtype'],
                $removedLpaData['lpa']['lpaReference'] ?? ''
            );
            $lpaType = $this->translator->translate($label, [], null, 'flashMessage');

            $message = $this->translator->translate(
                "You've removed %donor%'s %lpaType% LPA",
                [
                    '%donor%'   => $donor,
                    '%lpaType%' => $lpaType,
                ],
                null,
                'flashMessage'
            );
            $flash->flash(self::REMOVE_LPA_FLASH_MSG, $message);

            return $this->redirectToRoute('lpa.dashboard');
        }

        throw new BadRequestException('Invalid Form');
    }
}
