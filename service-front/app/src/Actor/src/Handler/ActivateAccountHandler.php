<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Service\User\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Class ActivateAccountHandler
 * @package Actor\Handler
 */
class ActivateAccountHandler extends AbstractHandler
{
    /** @var UserService */
    private $userService;

    /**
     * ActivateAccountHandler constructor.
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
        $activationToken = $request->getAttribute('token');

        $userData = $this->userService->activate($activationToken);

        //  TODO - complete this later
        //  For now just redirect to screen with active on it
        echo 'USER ACTIVE!';
        echo '<br/><br/><a href="/">HOME</a>';
        die();
    }
}
