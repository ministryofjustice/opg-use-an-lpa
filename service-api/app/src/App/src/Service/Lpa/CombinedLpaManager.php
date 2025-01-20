<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\Lpa;
use App\DataAccess\Repository\Response\LpaInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Lpa\Combined\FilterActiveActors;
use App\Service\Lpa\Combined\ResolveLpaTypes;
use DateTimeInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * @psalm-import-type UserLpaActorMap from UserLpaActorMapInterface
 */
class CombinedLpaManager implements LpaManagerInterface
{
    public function __construct(
        private readonly UserLpaActorMapInterface $userLpaActorMapRepository,
        private readonly ResolveLpaTypes $resolveLpaTypes,
        private readonly SiriusLpas $siriusLpas,
        private readonly DataStoreLpas $dataStoreLpas,
        private readonly ResolveActor $resolveActor,
        private readonly IsValidLpa $isValidLpa,
        private readonly FilterActiveActors $filterActiveActors,
    ) {
    }

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

    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        $lpaActorMap = $this->userLpaActorMapRepository->get($token);

        // Ensure the passed userId matches the passed token
        if ($userId !== $lpaActorMap['UserId']) {
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

        // Extract and return only LPA's where status is Registered or Cancelled
        if (($this->isValidLpa)($lpaData)) {
            return $result;
        }

        // LPA was found but is not valid for use.
        // TODO UML-3777 Investigate why an empty array is returned here and not a null. Return a null if we can.
        return [];
    }

    public function getAllActiveForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getByUserId($userId);

        $lpaActorMaps = array_filter($lpaActorMaps, function ($item) {
            return !array_key_exists('ActivateBy', $item);
        });

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

    public function getAllForUser(string $userId): array
    {
        // Returns an array of all the LPAs Ids (plus other metadata) in the user's account.
        $lpaActorMaps = $this->userLpaActorMapRepository->getByUserId($userId);

        return $this->lookupAndFormatLpas($lpaActorMaps);
    }

    public function getByViewerCode(string $viewerCode, string $donorSurname, ?string $organisation = null): ?array
    {
        throw new ApiException('Not implemented');
    }

    /**
     * @param $lpaActorMaps array Map of LPAs from Dynamo
     * @psalm-param $lpaActorMaps UserLpaActorMap[]
     * @return array an array with formatted LPA results
     * @throws ApiException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
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
     * @psalm-param UserLpaActorMap $lpaActorMap
     * @return LpaInterface|null
     * @throws ApiException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
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
     * @psalm-param UserLpaActorMap[] $lpaActorMaps
     * @return LpaInterface[]
     * @throws ApiException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    private function lookupLpas(array $lpaActorMaps): array
    {
        [$siriusUids, $dataStoreUids] = ($this->resolveLpaTypes)($lpaActorMaps);

        /** @var LpaInterface[] $siriusLpas */
        $siriusLpas = count($siriusUids) > 0
            ? $this->siriusLpas->lookup($siriusUids)
            : [];

        /** @var LpaInterface[] $siriusLpas */
        $dataStoreLpas = count($dataStoreUids) > 0
            ? $this->dataStoreLpas->lookup($dataStoreUids)
            : [];

        $keyedDataStoreLpas = [];
        array_walk($dataStoreLpas, function (LpaInterface $item) use (&$keyedDataStoreLpas) {
            $keyedDataStoreLpas[$item->getData()->getUid()] = $item;
        });

        // unusual combination operation in order to preserve potential numeric keys
        return $keyedDataStoreLpas + $siriusLpas;
    }
}
