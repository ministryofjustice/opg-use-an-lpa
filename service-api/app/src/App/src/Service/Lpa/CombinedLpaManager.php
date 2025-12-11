<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\{DataStoreLpas, SiriusLpas};
use DateTimeImmutable;
use App\DataAccess\Repository\{InstructionsAndPreferencesImagesInterface,
    Response\Lpa,
    Response\LpaInterface,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use App\Enum\LpaSource;
use App\Exception\{ApiException, MissingCodeExpiryException, NotFoundException};
use App\Service\Lpa\Combined\{FilterActiveActors, RejectInvalidLpa, ResolveLpaTypes,
    UserLpaActorToken as UserLpaActorTokenResponse};
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\ResolveActor\HasActorInterface;
use App\Value\LpaUid;
use DateTimeInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type UserLpaActorMap from UserLpaActorMapInterface
 */
class CombinedLpaManager implements LpaManagerInterface
{
    public function __construct(
        private readonly UserLpaActorMapInterface $userLpaActorMap,
        private readonly SiriusLpas $siriusLpas,
        private readonly DataStoreLpas $dataStoreLpas,
        private readonly ViewerCodesInterface $viewerCodes,
        private readonly ViewerCodeActivityInterface $viewerCodeActivity,
        private readonly InstructionsAndPreferencesImagesInterface $instructionsAndPreferencesImages,
        private readonly ResolveLpaTypes $resolveLpaTypes,
        private readonly ResolveActor $resolveActor,
        private readonly IsValidLpa $isValidLpa,
        private readonly FilterActiveActors $filterActiveActors,
        private readonly RejectInvalidLpa $rejectInvalidLpa,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getByUid(LpaUid $uid, ?string $originatorId = null): ?LpaInterface
    {
        $lpa = match ($uid->getLpaSource()) {
            LpaSource::SIRIUS => $this->siriusLpas->get($uid->getLpaUid()),
            LpaSource::LPASTORE => $this->dataStoreLpas->setOriginatorId($originatorId ?? '')->get($uid->getLpaUid()),
        };

        if ($lpa === null) {
            return null;
        }

        /** @var \App\Entity\Lpa $lpaData */
        $lpaData = ($this->filterActiveActors)($lpa->getData());

        return new Lpa(
            $lpaData,
            $lpa->getLookupTime(),
        );
    }

    public function getByUserLpaActorToken(string $token, string $userId): ?UserLpaActorTokenResponse
    {
        $lpaActorMap = $this->userLpaActorMap->get($token);

        // Ensure the passed userId matches the passed token
        if ($lpaActorMap === null || $userId !== $lpaActorMap['UserId']) {
            throw new NotFoundException();
        }

        $lpa = $this->lookupLpa($lpaActorMap, $userId);

        if ($lpa === null) {
            throw new NotFoundException();
        }

        /** @var \App\Entity\Lpa $lpaData */
        $lpaData = ($this->filterActiveActors)($lpa->getData());

        $result = new UserLpaActorTokenResponse(
            $lpaActorMap['Id'],
            $lpa->getLookupTime(),
            $lpaData
        );

        if (isset($lpaActorMap['DueBy'])) {
            $result = $result->withActivationKeyDueDate(new DateTimeImmutable($lpaActorMap['DueBy']));
        }

        if (isset($lpaActorMap['HasPaperVerificationCode'])) {
            $result = $result->withHasPaperVerificationCode($lpaActorMap['HasPaperVerificationCode']);
        }

        // If an actor has been stored against an LPA then attempt to resolve it from the API return
        if (isset($lpaActorMap['ActorId'])) {
            // If an active attorney is not found then this is null
            $result = $result->withActor(($this->resolveActor)($lpaData, (string) $lpaActorMap['ActorId']));
        }

        // Extract and return only LPAs where status is Registered or Cancelled
        if (($this->isValidLpa)($lpaData)) {
            return $result;
        }

        // LPA was found but is not valid for use.
        return null;
    }

    public function getAllActiveForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMap->getByUserId($userId);

        $lpaActorMaps = array_filter($lpaActorMaps, function (array $item) {
            return !array_key_exists('ActivateBy', $item);
        });

        return $this->lookupAndFormatLpas($lpaActorMaps, $userId);
    }

    public function getAllForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMap->getByUserId($userId);

        return $this->lookupAndFormatLpas($lpaActorMaps, $userId);
    }

    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): array
    {
        $viewerCodeData = $this->viewerCodes->get($viewerCode);

        if (is_null($viewerCodeData)) {
            $this->logger->info('The code entered by user to view LPA is not found in the database.');
            throw new NotFoundException();
        }

        $lpaId = new LpaUid($viewerCodeData['LpaUid'] ?? $viewerCodeData['SiriusUid']);
        $lpa   = $this->getByUid($lpaId, 'V-' . $viewerCode);

        if ($lpa === null) {
            throw new NotFoundException();
        }

        // Whilst the checks in this invokable could be done before we look up the LPA, they are done
        // at this point as we only want to acknowledge if a code has expired if the donor surname matched.
        try {
            ($this->rejectInvalidLpa)($lpa, $viewerCode, $donorSurname, $viewerCodeData);
        } catch (MissingCodeExpiryException) {
            throw ApiException::create('Missing code expiry data in Dynamo response');
        }

        /** @var \App\Entity\Lpa $lpaObj */
        $lpaObj = $lpa->getData();

        $result = [
            'date'         => $lpa->getLookupTime()->format(DateTimeInterface::ATOM),
            'expires'      => $viewerCodeData['Expires']->format(DateTimeInterface::ATOM),
            'organisation' => $viewerCodeData['Organisation'],
            'lpa'          => $lpaObj,
        ];

        // As this method is only ever really hit by the viewer side of the app we'll always need the images
        // if there are any so we skip that extra round trip and do that fetch now.
        if (
            ($lpaObj->applicationHasGuidance ?? false) || ($lpaObj->applicationHasRestrictions ?? false)
        ) {
            $this->logger->info('The LPA has instructions and/or preferences. Fetching images');
            $result['iap'] =
                $this->instructionsAndPreferencesImages->getInstructionsAndPreferencesImages((int) $lpaObj->uId);
        }

        if ($organisation !== null) {
            // Record the lookup in the activity table
            // We only do this if the organisation is provided
            $this->viewerCodeActivity->recordSuccessfulLookupActivity(
                $viewerCodeData['ViewerCode'],
                $organisation
            );
        }

        return $result;
    }

    /**
     * @param       $lpaActorMaps array Map of LPAs from Dynamo
     * @psalm-param $lpaActorMaps UserLpaActorMap[]
     * @return array an array with formatted LPA results
     * @throws ApiException
     */
    private function lookupAndFormatLpas(array $lpaActorMaps, string $userId): array
    {
        $lpas   = $this->lookupLpas($lpaActorMaps, $userId);
        $result = [];

        foreach ($lpaActorMaps as $item) {
            $lpaId = $item['LpaUid'] ?? $item['SiriusUid'];
            $lpa   = $lpas[$lpaId] ?? null;

            if ($lpa === null) {
                $result[$item['Id']] = [
                    'user-lpa-actor-token' => $item['Id'],
                    'error'                => 'NO_LPA_FOUND',
                ];

                continue;
            }

            /** @var HasActorInterface&IsValidInterface $lpaData */
            $lpaData = $lpa->getData();

            $actor = ($this->resolveActor)($lpaData, (string) $item['ActorId']);

            //Extract and return only LPA's where status is Registered or Cancelled
            if (($this->isValidLpa)($lpaData)) {
                $result[$item['Id']] = [
                    'user-lpa-actor-token' => $item['Id'],
                    'date'                 => $lpa->getLookupTime()->format(DateTimeInterface::ATOM),
                    'actor'                => $actor,
                    'lpa'                  => $lpaData,
                    'added'                => $item['Added']->format(DateTimeInterface::ATOM),
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $lpaActorMap
     * @psalm-param $lpaActorMap UserLpaActorMap
     * @param string $originatorId
     * @return LpaInterface|null
     * @throws ApiException
     */
    private function lookupLpa(array $lpaActorMap, string $originatorId): ?LpaInterface
    {
        [$siriusUids, $dataStoreUids] = ($this->resolveLpaTypes)([$lpaActorMap]);

        return count($siriusUids) > 0
            ? $this->siriusLpas->get($siriusUids[0])
            : $this->dataStoreLpas->setOriginatorId($originatorId)->get($dataStoreUids[0]);
    }

    /**
     * @param array $lpaActorMaps
     * @psalm-param $lpaActorMaps UserLpaActorMap[]
     * @param string $originatorId
     * @return LpaInterface[]
     * @throws ApiException
     */
    private function lookupLpas(array $lpaActorMaps, string $originatorId): array
    {
        [$siriusUids, $dataStoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $siriusLpas = count($siriusUids) > 0
            ? $this->siriusLpas->lookup($siriusUids)
            : [];

        $dataStoreLpas = count($dataStoreUids) > 0
            ? $this->dataStoreLpas->setOriginatorId($originatorId)->lookup($dataStoreUids)
            : [];

        $keyedDataStoreLpas = [];
        array_walk($dataStoreLpas, function (LpaInterface $item) use (&$keyedDataStoreLpas) {
            $keyedDataStoreLpas[$item->getData()->getUid()] = $item;
        });

        $this->logger->info(
            'Found {count} LPAs in account and was able to load {loaded} from upstream',
            [
                'count'    => count($siriusUids) + count($dataStoreUids),
                'loaded'   => count($siriusLpas) + count($dataStoreLpas),
                'sirius'   => sprintf('%d found, %d loaded', count($siriusUids), count($siriusLpas)),
                'lpastore' => sprintf('%d found, %d loaded', count($dataStoreUids), count($dataStoreLpas)),
            ],
        );

        // unusual combination operation in order to preserve potential numeric keys
        return $keyedDataStoreLpas + $siriusLpas;
    }
}
