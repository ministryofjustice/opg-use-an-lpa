<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateAccount;
use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Csrf\CsrfGuardInterface;
use Zend\Expressive\Csrf\CsrfMiddleware;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class CreateAccountHandler
 * @package Actor\Handler
 */
class CreateAccountHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /**
     * CreateAccountHandler constructor.
     * @param TemplateRendererInterface $renderer
     * @param UrlHelper $urlHelper
     * @param UserService $userService
     */
    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        UserService $userService)
    {
        parent::__construct($renderer, $urlHelper);

        $this->userService = $userService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        /** @var CsrfGuardInterface $guard */
        $guard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        $form = new CreateAccount($guard);

        if ($request->getMethod() === 'POST') {
            $form->setData($request->getParsedBody());

            if ($form->isValid()) {
                $data = $form->getData();

                $userData = $this->userService->create($data['email'], $data['password']);

                //  TODO - For now just redirect to create account page
                return $this->redirectToRoute('create-account');
            }
        }

        return new HtmlResponse($this->renderer->render('actor::create-account',[
            'form' => $form
        ]));
    }
}
