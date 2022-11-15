<?php

declare(strict_types=1);

namespace Actor\Handler;

use Actor\Form\CancelCode;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Common\Exception\InvalidRequestException;
use Common\Handler\{AbstractHandler, CsrfGuardAware, Traits\CsrfGuard, Traits\Session, Traits\User, UserAware};
use Common\Service\Lpa\{LpaService, ViewerCodeService};
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class CheckAccessCodesHandler extends AbstractHandler implements UserAware, CsrfGuardAware
{
    use CsrfGuard;
    use Session;
    use User;

    public function __construct(
        TemplateRendererInterface $renderer,
        UrlHelper $urlHelper,
        AuthenticationInterface $authenticator,
        private LpaService $lpaService,
        private ViewerCodeService $viewerCodeService,
        LoggerInterface $logger,
    ) {
        parent::__construct($renderer, $urlHelper, $logger);

        $this->setAuthenticator($authenticator);
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

        $user     = $this->getUser($request);
        $identity = !is_null($user) ? $user->getIdentity() : null;

        $lpaData = $this->lpaService->getLpaById($identity, $actorLpaToken);

        // TODO UML-1394 TO BE REMOVED IN FUTURE TO SHOW PAGE NOT FOUND WITH APPROPRIATE CONTENT
        if (is_null($lpaData)) {
            return $this->redirectToRoute('lpa.dashboard');
        }

        /**
         * @var Lpa $lpa
         * @psalm-suppress UndefinedPropertyFetch # Psalm doesn't like ArrayObjects, and why should it?
         */
        $lpa = $lpaData->lpa;

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $identity,
            $actorLpaToken,
            false
        );

        foreach ($shareCodes as $key => $code) {
            if (
                new DateTime($code['Expires']) >= (new DateTime('now'))->setTime(23, 59, 59)
                && !array_key_exists('Cancelled', $code)
            ) {
                $form = new CancelCode($this->getCsrfGuard($request));
                $form->setAttribute('action', $this->urlHelper->generate('lpa.confirm-cancel-code'));

                $form->setData([
                    'lpa_token'    => $actorLpaToken,
                    'viewer_code'  => $code['ViewerCode'],
                    'organisation' => $code['Organisation'],
                ]);

                $shareCodes[$key]['form'] = $form;
            }

            if (!isset($code['ActorId'])) {
                continue;
            }

            $this->logger->debug(
                'Resolved actor id to {type}:{actor_id}',
                [
                    'actor_id' => $code['ActorId'],
                    'type'     => gettype($code['ActorId']),
                ]
            );

            $this->logger->debug(
                'Donor Id is {type}:{donor_id}',
                [
                    'donor_id' => $lpa->getDonor()->getUId(),
                    'type'     => gettype($lpa->getDonor()->getUId()),
                ]
            );

            if ($this->idMatch($lpa->getDonor(), $code)) {
                $shareCodes[$key]['CreatedBy'] =
                    $lpa->getDonor()->getFirstname() . ' ' . $lpa->getDonor()->getSurname();
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
                    $shareCodes[$key]['CreatedBy'] = $attorney->getFirstname() . ' ' . $attorney->getSurname();
                }
            }
            if (empty($shareCodes[$key]['CreatedBy'])) {
                foreach ($lpa->getTrustCorporations() as $trustCorporation) {
                    $this->logger->debug(
                        'Looking for attorney id {type}:{attorney_id} in Trust Corporations',
                        [
                            'attorney_id' => $trustCorporation->getUId(),
                            'type'        => gettype($trustCorporation->getUId()),
                        ]
                    );

                    if ($this->idMatch($trustCorporation, $code)) {
                        $shareCodes[$key]['CreatedBy'] = $trustCorporation->getCompanyName();
                    }
                }
            }

            $this->logger->debug(
                'Created by resolved to {actor_name}',
                [
                    'actor_name' => $shareCodes[$key]['CreatedBy'] ?? 'NULL',
                ]
            );
        }

        /** @var FlashMessagesInterface $flash */
        $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        return new HtmlResponse($this->renderer->render('actor::check-access-codes', [
            'actorToken' => $actorLpaToken,
            'user'       => $user,
            'lpa'        => $lpa,
            'shareCodes' => $shareCodes,
            'flash'      => $flash,
        ]));
    }

    private function idMatch(CaseActor $actor, array $code): bool
    {
            return $actor->getId() === $code['ActorId']
            || intval($actor->getUId()) === $code['ActorId'];
    }
}
