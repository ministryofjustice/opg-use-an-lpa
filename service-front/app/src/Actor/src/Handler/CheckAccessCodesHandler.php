<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CancelCode;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Entity\Person;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session};
use Common\Service\Features\FeatureEnabled;
use Common\Service\Lpa\{LpaService, ViewerCodeService};
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
final class CheckAccessCodesHandler extends AbstractHandler implements CsrfGuardAware
{
    use CsrfGuard;
    use Session;

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
        /** @var string $actorLpaToken */
        $actorLpaToken = $request->getQueryParams()['lpa'];

        if (is_null($actorLpaToken)) {
            throw new InvalidRequestException('No actor-lpa token specified');
        }

        $lpaData = $this->lpaService->getLpaById($actorLpaToken);

        if ($lpaData === null) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        /**
         * @var Lpa $lpa
         * @psalm-suppress UndefinedPropertyFetch # Psalm doesn't like ArrayObjects, and why should it?
         */
        $lpa = $lpaData->lpa;

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $actorLpaToken,
            false
        );

        foreach ($shareCodes as $key => $code) {
            if (
                new DateTime($code['Expires']) >= (new DateTime('now'))->setTime(23, 59, 59)
                && !array_key_exists('Cancelled', $code)
                && $actorLpaToken === $code['UserLpaActor']
            ) {
                $shareCodes[$key]['form'] = $this->createCancelCodeForm($request, $actorLpaToken, $code);
            }

            $shareCodes[$key] = $this->resolveCodeCreator($lpa, $shareCodes[$key]);
        }

        $templateName = 'actor::check-access-codes';
        if (($this->featureEnabled)('support_datastore_lpas')) {
            $templateName = 'actor::check-access-codes-combined-lpa';
        }

        return new HtmlResponse($this->renderer->render($templateName, [
            'actorToken' => $actorLpaToken,
            'lpa'        => $lpa,
            'shareCodes' => $shareCodes,
        ]));
    }

    private function createCancelCodeForm(
        ServerRequestInterface $request,
        string $actorLpaToken,
        array $code,
    ): CancelCode {
        $csrfGuard = $this->getCsrfGuard($request);
        if ($csrfGuard === null) {
            throw new InvalidRequestException('CSRF guard is unavailable');
        }

        $form = new CancelCode($csrfGuard);
        $form->setAttribute('action', $this->urlHelper->generate('lpa.confirm-cancel-code'));
        $form->setData([
            'lpa_token'    => $actorLpaToken,
            'viewer_code'  => $code['ViewerCode'],
            'organisation' => $code['Organisation'],
        ]);

        return $form;
    }

    private function resolveCodeCreator(Lpa $lpa, array $code): array
    {
        if (!isset($code['ActorId'])) {
            return $code;
        }

        $this->logger->debug(
            'Resolved actor id to {type}:{actor_id}',
            [
                'actor_id' => $code['ActorId'],
                'type'     => gettype($code['ActorId']),
            ]
        );

        $donor = $lpa->getDonor();

        if ($donor !== null) {
            $this->logger->debug(
                'Donor Id is {type}:{donor_id}',
                [
                    'donor_id' => $donor->getUId(),
                    'type'     => gettype($donor->getUId()),
                ]
            );

            if ($this->idMatch($donor, $code)) {
                $code['CreatedBy'] = ($donor->getFirstname() ?? '') . ' ' . ($donor->getSurname() ?? '');
            }
        }

        foreach ($lpa->getAttorneys() as $attorney) {
            $this->logger->debug(
                'Attorney Id is {type}:{attorney_id}',
                [
                    'attorney_id' => $attorney->getUId(),
                    'type'        => gettype($attorney->getUId()),
                ]
            );

            if ($this->idMatch($attorney, $code)) {
                $code['CreatedBy'] = ($attorney->getFirstname() ?? '') . ' ' . ($attorney->getSurname() ?? '');
            }
        }

        foreach ($lpa->getTrustCorporations() as $trustCorporation) {
            $this->logger->debug(
                'Looking for attorney id {type}:{attorney_id} in Trust Corporations',
                [
                    'attorney_id' => $trustCorporation->getUId(),
                    'type'        => gettype($trustCorporation->getUId()),
                ]
            );

            if ($this->idMatch($trustCorporation, $code)) {
                $code['CreatedBy'] = $trustCorporation->getCompanyName();
            }
        }

        $this->logger->debug(
            'Created by resolved to {actor_name}',
            [
                'actor_name' => $code['CreatedBy'] ?? 'NULL',
            ]
        );

        return $code;
    }

    private function idMatch(CaseActor|Person $actor, array $code): bool
    {
            return $actor->getId() === $code['ActorId']
            || intval($actor->getUId()) === $code['ActorId'];
    }
}
