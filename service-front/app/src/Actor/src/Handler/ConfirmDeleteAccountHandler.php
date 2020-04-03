<?php

declare(strict_types=1);

namespace Actor\Handler;

use App\Exception\BadRequestException;
use Common\Exception\ApiException;
use Common\Handler\AbstractHandler;
use Common\Handler\LoggerAware;
use Common\Handler\Traits\Logger;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class ConfirmDeleteAccountHandler
 * @package Actor\Handler
 */
class ConfirmDeleteAccountHandler extends AbstractHandler implements UserAware, LoggerAware
{
    use User;
    use Logger;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authentication,
        LoggerInterface $logger
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
        $this->setAuthenticator($authentication);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::confirm-delete-account', [
            'user' => $user
        ]));
    }

}
