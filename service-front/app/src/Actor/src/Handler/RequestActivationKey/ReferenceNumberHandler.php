<?php

declare(strict_types=1);

namespace Actor\Handler\RequestActivationKey;

use Actor\Form\RequestActivationKey\RequestReferenceNumber;
use Common\Handler\{CsrfGuardAware, UserAware};
use Common\Service\Features\FeatureEnabled;
use Common\Workflow\WorkflowState;
use Common\Workflow\WorkflowStep;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * Class ReferenceNumberHandler
 * @package Actor\Handler\RequestActivationKey
 * @codeCoverageIgnore
 */
class ReferenceNumberHandler extends AbstractRequestKeyHandler implements UserAware, CsrfGuardAware, WorkflowStep
{
    private RequestReferenceNumber $form;
    private FeatureEnabled $featureEnabled;

    public function __construct(
        TemplateRendererInterface $renderer,
        AuthenticationInterface $authenticator,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        FeatureEnabled $featureEnabled
    ) {
        parent::__construct($renderer, $authenticator, $urlHelper, $logger);
        $this->featureEnabled = $featureEnabled;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->form = new RequestReferenceNumber(
            $this->getCsrfGuard($request),
            ($this->featureEnabled)('allow_meris_lpas')
        );

        return parent::handle($request);
    }

    public function handleGet(ServerRequestInterface $request): ResponseInterface
    {
        if (array_key_exists('startAgain', $request->getQueryParams())) {
            $this->state($request)->reset();
        }

        $this->form->setData(
            [
                'opg_reference_number' => $this->state($request)->referenceNumber
            ]
        );

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/reference-number',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request))
                ]
            )
        );
    }

    public function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $this->form->setData($request->getParsedBody());
        if ($this->form->isValid()) {
            $postData = $this->form->getData();

            //  Set the data in the session and pass to the check your answers handler
            $this->state($request)->referenceNumber = (int) $postData['opg_reference_number'];

            return $this->redirectToRoute($this->nextPage($this->state($request)));
        }

        return new HtmlResponse(
            $this->renderer->render(
                'actor::request-activation-key/reference-number',
                [
                    'user' => $this->user,
                    'form' => $this->form->prepare(),
                    'back' => $this->lastPage($this->state($request))
                ]
            )
        );
    }

    public function isMissingPrerequisite(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function nextPage(WorkflowState $state): string
    {
        return $state->has('postcode') ? 'lpa.check-answers' : 'lpa.your-name';
    }

    public function lastPage(WorkflowState $state): string
    {
        return $state->has('postcode') ? 'lpa.check-answers' : 'lpa.add-by-paper-information';
    }
}
