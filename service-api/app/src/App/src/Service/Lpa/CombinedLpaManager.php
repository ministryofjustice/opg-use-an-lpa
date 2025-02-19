<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\{DataStoreLpas, SiriusLpas};
use App\DataAccess\Repository\{InstructionsAndPreferencesImagesInterface,
    Response\Lpa,
    Response\LpaInterface,
    UserLpaActorMapInterface,
    ViewerCodeActivityInterface,
    ViewerCodesInterface};
use App\Exception\{ApiException, MissingCodeExpiryException, NotFoundException};
use App\Service\Lpa\Combined\{FilterActiveActors, RejectInvalidLpa, ResolveLpaTypes};
use App\Service\Lpa\IsValid\IsValidInterface;
use App\Service\Lpa\ResolveActor\HasActorInterface;
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

    /**
     * @inheritDoc
     */
    public function getByUid(string $uid): ?LpaInterface
    {
        $lpa = str_starts_with($uid, '7')
            ? $this->siriusLpas->get($uid)
            : $this->dataStoreLpas->get($uid);

        if ($lpa === null) {
            return null;
        }

        $lpaData = ($this->filterActiveActors)($lpa->getData());

        return new Lpa(
            $lpaData,
            $lpa->getLookupTime(),
        );
    }

    /**
     * @inheritDoc
     */
    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        $lpaActorMap = $this->userLpaActorMap->get($token);

        // Ensure the passed userId matches the passed token
        if ($lpaActorMap === null || $userId !== $lpaActorMap['UserId']) {
            return null;
        }

        $lpa = $this->lookupLpa($lpaActorMap);

        if ($lpa === null) {
            return null;
        }

        $lpaData = ($this->filterActiveActors)($lpa->getData());

        $result = [
            'user-lpa-actor-token' => $lpaActorMap['Id'],
            'date'                 => $lpa->getLookupTime()->format(DateTimeInterface::ATOM),
            'lpa'                  => $lpaData,
            'activationKeyDueDate' => $lpaActorMap['DueBy'] ?? null,
        ];

        // If an actor has been stored against an LPA then attempt to resolve it from the API return
        if (isset($lpaActorMap['ActorId'])) {
            // If an active attorney is not found then this is null
            $result['actor'] = ($this->resolveActor)($lpaData, (string) $lpaActorMap['ActorId']);
        }

        // Extract and return only LPAs where status is Registered or Cancelled
        if (($this->isValidLpa)($lpaData)) {
            return $result;
        }

        // LPA was found but is not valid for use.
        // TODO UML-3777 Investigate why an empty array is returned here and not a null. Return a null if we can.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAllActiveForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMap->getByUserId($userId);

        $lpaActorMaps = array_filter($lpaActorMaps, function (array $item) {
            return !array_key_exists('ActivateBy', $item);
        });

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

    /**
     * @inheritDoc
     */
    public function getAllForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMap->getByUserId($userId);

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

    /**
     * @inheritDoc
     */
    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): array
    {
        $viewerCodeData = $this->viewerCodes->get($viewerCode);

        if (is_null($viewerCodeData)) {
            $this->logger->info('The code entered by user to view LPA is not found in the database.');
            throw new NotFoundException();
        }

        $lpaId = $viewerCodeData['LpaUid'] ?? $viewerCodeData['SiriusUid'];
        $lpa   = $this->getByUid($lpaId);

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
    private function lookupAndFormatLpas(array $lpaActorMaps): array
    {
        $lpas   = $this->lookupLpas($lpaActorMaps);
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
     * @param array                 $lpaActorMap
     * @psalm-param UserLpaActorMap $lpaActorMap
     * @return LpaInterface|null
     * @throws ApiException
     */
    private function lookupLpa(array $lpaActorMap): ?LpaInterface
    {
        [$siriusUids, $dataStoreUids] = ($this->resolveLpaTypes)([$lpaActorMap]);

        return count($siriusUids) > 0
            ? $this->siriusLpas->get($siriusUids[0])
            : $this->dataStoreLpas->get($dataStoreUids[0]);
    }

    /**
     * @param array $lpaActorMaps
     * @return LpaInterface[]
     * @throws ApiException
     */
    private function lookupLpas(array $lpaActorMaps): array
    {
        [$siriusUids, $dataStoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        $siriusLpas = count($siriusUids) > 0
            ? $this->siriusLpas->lookup($siriusUids)
            : [];

        $dataStoreLpas = count($dataStoreUids) > 0
            ? $this->dataStoreLpas->lookup($dataStoreUids)
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
