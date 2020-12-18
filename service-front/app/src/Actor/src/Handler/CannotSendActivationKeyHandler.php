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
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\{AuthenticationInterface, UserInterface};
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class CannotSendActivationKeyHandler
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class CannotSendActivationKeyHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use CsrfGuard;
    use SessionTrait;

    private CheckYourAnswers $form;
    private ?SessionInterface $session;
    private ?UserInterface $user;
    private array $data;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::cannot-send-activation-key'));
    }
}
