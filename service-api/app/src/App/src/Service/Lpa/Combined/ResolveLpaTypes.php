<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\DataAccess\Repository\UserLpaActorMapInterface;

/**
 * @psalm-import-type UserLpaActorMap from UserLpaActorMapInterface
 */
class ResolveLpaTypes
{
    /**
     * Given a list of UserLpaActorMap records it will return a two part array containing
     * separate lists of both Sirius and DataStore LPAs
     *
     * @psalm-pure
     * @param       $lpaActorMaps array Map of LPAs from Dynamo
     * @psalm-param $lpaActorMaps UserLpaActorMap[]
     * @return array{
     *     string[],
     *     string[]
     * }
     */
    public function __invoke(array $lpaActorMaps): array
    {
        $lpaUids = array_merge(
            array_column($lpaActorMaps, 'SiriusUid'),
            array_column($lpaActorMaps, 'LpaUid'),
        );

        $dataStoreUids = [];
        $siriusUids    = array_filter($lpaUids, function ($item) use (&$dataStoreUids) {
            if (str_starts_with($item, '7')) {
                return true;
            }

            $dataStoreUids[] = $item;
            return false;
        });

        return [$siriusUids, $dataStoreUids];
    }
}
