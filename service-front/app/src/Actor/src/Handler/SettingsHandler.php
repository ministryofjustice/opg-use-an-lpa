<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @codeCoverageIgnore
 */
class SettingsHandler extends AbstractHandler implements UserAware
{
    use User;

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        return new HtmlResponse($this->renderer->render('actor::settings', [
            'user' => $user,
        ]));
    }
}
