<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, SessionAware, Traits\Session as SessionTrait, Traits\User, UserAware};
use Common\Service\Features\FeatureEnabled;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @codeCoverageIgnore
 */
class RequestActivationKeyInfoHandler extends AbstractHandler implements SessionAware, UserAware
{
    use SessionTrait;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private FeatureEnabled $featureEnabled,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse($this->renderer->render(
            'actor::request-activation-key/before-requesting-activation-key-info',
            [
                'user' => $this->getUser($request),
            ]
        ));
    }
}
