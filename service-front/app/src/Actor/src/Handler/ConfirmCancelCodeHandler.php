<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CancelCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\{Lpa\LpaService, Lpa\ViewerCodeService};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;


/**
 * Class CancelCodeHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class ConfirmCancelCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use User;
    use Session;
    use CsrfGuard;

    /**
     * @var LpaService
     */
    private $lpaService;

    /**
     * @var ViewerCodeService
     */
    private $viewerCodeService;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        LpaService $lpaService,
        ViewerCodeService $viewerCodeService,
        UrlHelper $urlHelper)
    {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->lpaService = $lpaService;
        $this->viewerCodeService = $viewerCodeService;
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

        $form->setAttribute('action',$this->urlHelper->generate('lpa.cancel-code'));

        $user = $this->getUser($request);
        
        $form->setData($request->getParsedBody());

        if ($form->isValid()) {

            return new HtmlResponse($this->renderer->render('actor::confirm-cancel-code', [
                'form'          => $form,
                'user'          => $user,
            ]));
        }

        throw new InvalidRequestException('Invalid form submission');

    }
}