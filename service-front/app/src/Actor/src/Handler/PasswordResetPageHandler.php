<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\PasswordReset;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class PasswordResetPageHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class PasswordResetPageHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;

    /** @var UserService */
    private $userService;

    /** @var ServerUrlHelper */
    private $serverUrlHelper;

    /**
     * PasswordResetPageHandler constructor.
     *
     * @codeCoverageIgnore
     *
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     * @param ServerUrlHelper $serverUrlHelper
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService,
        ServerUrlHelper $serverUrlHelper
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
        $this->serverUrlHelper = $serverUrlHelper;
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new PasswordReset($this->getCsrfGuard($request));

        $tokenValid = $this->userService->canPasswordReset($request->getAttribute('token'));

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                $this->userService->completePasswordReset($request->getAttribute('token'), new HiddenString($data['password']));

                //  Redirect to the login screen with success flash message
                return $this->redirectToRoute('login');
            }
        }

        if ($tokenValid) {
            return new HtmlResponse($this->renderer->render('actor::password-reset', [
                'form' => $form->prepare()
            ]));
        }

        return new HtmlResponse($this->renderer->render('actor::password-reset-not-found'));
    }
}
