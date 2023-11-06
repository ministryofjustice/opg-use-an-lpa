<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Handler\AbstractHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class OneLoginCallbackHandler extends AbstractHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $authParams = $request->getQueryParams();

        if (!array_key_exists('code', $authParams) || !array_key_exists('state', $authParams)) {
            throw new RuntimeException('Required parameters not passed for authentication', 500);
        }

        return new HtmlResponse('<h1>Hello World</h1>');
    }
}
