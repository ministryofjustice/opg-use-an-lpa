<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Common\Handler\{AbstractHandler, Traits\Session as SessionTrait, Traits\User, UserAware};
use Common\Service\Features\FeatureEnabled;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * Class RequestActivationKeyInfoHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class RequestActivationKeyInfoHandler extends AbstractHandler implements UserAware
{
    use User;
    use SessionTrait;

    private FeatureEnabled $featureEnabled;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        FeatureEnabled $featureEnabled
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->featureEnabled = $featureEnabled;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request);

        if (($this->featureEnabled)('allow_older_lpas')) {
            return new HtmlResponse($this->renderer->render(
                'actor::request-activation-key/before-requesting-activation-key-info',
                [
                    'user' => $user,
                ]
            ));
        } else {
            return new HtmlResponse($this->renderer->render(
                'actor::request-activation-key/info',
                [
                    'user' => $user,
                ]
            ));
        }
    }
}
