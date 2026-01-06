<?php

declare(strict_types=1);

namespace App\Service\Lpa\Combined;

use App\DataAccess\Repository\UserLpaActorMapInterface;

/**
 * @psalm-import-type UserLpaActorMap from UserLpaActorMapInterface
 * @psalm-type SiriusUids = string[]
 * @psalm-type DataStoreUids = string[]
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
     * @psalm-return array{
     *     SiriusUids,
     *     DataStoreUids,
     * }
     */
    public function __invoke(array $lpaActorMaps): array
    {
        $lpaUids = array_merge(
            array_column($lpaActorMaps, 'SiriusUid'),
            array_column($lpaActorMaps, 'LpaUid'),
        );

        $siriusUids    = [];
        $dataStoreUids = array_values(
            array_filter($lpaUids, function ($item) use (&$siriusUids) {
                if (str_starts_with($item, 'M-')) {
                    return true;
                }

                $siriusUids[] = $item;
                return false;
            })
        );

        return [
            $siriusUids,
            $dataStoreUids,
        ];
    }
}
