<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CreateShareCode;
use Common\Exception\InvalidRequestException;
use Common\Handler\AbstractHandler;
use Common\Handler\CsrfGuardAware;
use Common\Handler\Traits\CsrfGuard;
use Common\Handler\Traits\Session;
use Common\Handler\Traits\User;
use Common\Handler\UserAware;
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class CreateViewerCodeHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use CsrfGuard;
    use Session;
    use User;

    private CreateShareCode $form;
    private ?string $identity;
    private ?UserInterface $user;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        private LpaService $lpaService,
        private ViewerCodeService $viewerCodeService,
        private FeatureEnabled $featureEnabled,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);
    }

    /**
     * Handles a request and produces a response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new CreateShareCode($this->getCsrfGuard($request));

        $this->user     = $this->getUser($request);
        $this->identity = !is_null($this->user) ? $this->user->getIdentity() : null;

        return match ($request->getMethod()) {
            'POST' => $this->handlePost($request),
            default => $this->handleGet($request),
        };
    }

    protected function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        // the lpa actor token is either a query parameter or a form value.
        // get it from the form if it doesn't exist as a parameter
        if (isset($request->getQueryParams()['lpa'])) {
            $this->form->setData(['lpa_token' => $request->getQueryParams()['lpa']]);
        }

        if (is_null($this->form->get('lpa_token')->getValue())) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $lpaData = $this->lpaService->getLpaById($this->identity, $this->form->get('lpa_token')->getValue());

        //UML-1394 TO BE REMOVED IN FUTURE TO SHOW PAGE NOT FOUND WITH APPROPRIATE CONTENT
        if (is_null($lpaData)) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        $templateName = 'actor::lpa-create-viewercode';
        if (($this->featureEnabled)('support_datastore_lpas')) {
            $templateName = 'actor::lpa-create-viewercode-combined-lpa';
        }

        return new HtmlResponse($this->renderer->render($templateName, [
            'user'                     => $this->user,
            'lpa'                      => $lpaData->lpa,
            'hasPaperVerificationCode' => $lpaData->hasPaperVerificationCode ?? false,
            'actorToken'               => $this->form->get('lpa_token')->getValue(),
            'form'                     => $this->form,
        ]));
    }

    protected function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());

        if ($this->form->isValid()) {
            $validated = $this->form->getData();

            $codeData = $this->viewerCodeService->createShareCode(
                $this->identity,
                $validated['lpa_token'],
                $validated['org_name']
            );

            $lpaData   = $this->lpaService->getLpaById($this->identity, $validated['lpa_token']);
            $actorRole = $lpaData['actor']['type'] === 'donor' ? 'Donor' : 'Attorney';

            $templateName = 'actor::lpa-show-viewercode';
            if (($this->featureEnabled)('support_datastore_lpas')) {
                $templateName = 'actor::lpa-show-viewercode-combined-lpa';
            }

            return new HtmlResponse($this->renderer->render($templateName, [
                'user'         => $this->user,
                'actorToken'   => $validated['lpa_token'],
                'code'         => $codeData['code'],
                'expires'      => new DateTimeImmutable($codeData['expires']),
                'organisation' => ucwords($codeData['organisation']),
                'lpa'          => $lpaData->lpa,
                'actorRole'    => $actorRole,
            ]));
        }

        // form is invalid, show the page with errors
        return $this->handleGet($request);
    }
}
