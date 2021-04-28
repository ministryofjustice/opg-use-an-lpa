<?php

declare(strict_types=1);

namespace Actor\Handler;

use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Lpa\RemoveLpa;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RemoveLpaHandler
 *
 * @package Actor\Handler
 * @codeCoverageIgnore
 */
class RemoveLpaHandler extends AbstractHandler implements UserAware
{
    use User;

    /** @var RemoveLpa */
    private $removeLpa;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        RemoveLpa $removeLpa
    ) {
        parent::__construct($renderer, $urlHelper);

        $this->setAuthenticator($authenticator);
        $this->removeLpa = $removeLpa;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getUser($request)->getIdentity();
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $removedLpaData = ($this->removeLpa)($user, $actorLpaToken);
        return $this->redirectToRoute('lpa.dashboard');
    }
}
