<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\AddLpaTriage;
use Common\Handler\{AbstractHandler, CsrfGuardAware, LoggerAware, UserAware};
use Common\Handler\Traits\{CsrfGuard, User};
use Common\Handler\Traits\Logger;
use Common\Service\Log\EventCodes;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class AddLpaTriageHandler extends AbstractHandler implements UserAware, CsrfGuardAware, LoggerAware
{
    use CsrfGuard;
    use Logger;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new AddLpaTriage($this->getCsrfGuard($request));

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        return new HtmlResponse($this->renderer->render('actor::add-lpa-triage', [
            'user' => $this->getUser($request),
            'form' => $form->prepare(),
        ]));
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $form = new AddLpaTriage($this->getCsrfGuard($request));
        $form->setData($request->getParsedBody());

        if ($form->isValid()) {
            $selected = $form->getData()['activation_key_triage'];

            $this->getLogger()->notice(
                'User wants to add a LPA and their activation key status is {key_status}',
                [
                    'key_status' => $selected === 'Yes'
                        ? EventCodes::ACTIVATION_KEY_EXISTS
                        : ($selected === 'No'
                            ? EventCodes::ACTIVATION_KEY_NOT_EXISTS
                            : EventCodes::ACTIVATION_KEY_EXPIRED),
                ]
            );

            if ($selected === 'Yes') {
                return $this->redirectToRoute('lpa.add-by-key');
            }
            return $this->redirectToRoute('lpa.add-by-paper-information');
        }

        return new HtmlResponse($this->renderer->render('actor::add-lpa-triage', [
            'user' => $this->getUser($request),
            'form' => $form->prepare(),
        ]));
    }
}
