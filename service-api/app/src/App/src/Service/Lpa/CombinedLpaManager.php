<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\DataAccess\ApiGateway\DataStoreLpas;
use App\DataAccess\ApiGateway\SiriusLpas;
use App\DataAccess\Repository\Response\LpaInterface;
use App\DataAccess\Repository\UserLpaActorMapInterface;
use App\Exception\ApiException;
use App\Service\Lpa\Combined\ResolveLpaTypes;

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
        private ResolveActor $resolveActor,
        private IsValidLpa $isValidLpa,
    ) {
    }

    public function getByUid(string $uid): ?LpaInterface
    {
        throw new ApiException('Not implemented');
    }

    public function getByUserLpaActorToken(string $token, string $userId): ?array
    {
        throw new ApiException('Not implemented');
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
     */
    private function lookupAndFormatLpas(array $lpaActorMaps): array
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
            $keyedDataStoreLpas[$item->getData()->uId] = $item;
        });

        // unusual combination operation in order to preserve potential numeric keys
        $lpas = $keyedDataStoreLpas + $siriusLpas;

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
                    'date'                 => $lpa->getLookupTime()->format('c'),
                    'actor'                => $actor,
                    'lpa'                  => $lpaData,
                    'added'                => $item['Added']->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $result;
    }
}
