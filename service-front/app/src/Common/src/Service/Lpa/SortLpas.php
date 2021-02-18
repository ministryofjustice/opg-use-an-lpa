<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Class SortLpas
 *
 * Single action invokeable that sorts incoming LPA array objects according to our criteria.
 * Sort order starts with Surname and uses Firstname and case subtypes to further sort.
 *
 * @package Common\Service\Lpa
 */
class SortLpas
{
    /**
     * Sorts the two LPAs by Surname, forename, case-subtype and finally the added date.
     *
     * @param ArrayObject $lpas
     *
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas): ArrayObject
    {
        $lpas = $lpas->getArrayCopy();

        uasort($lpas, function ($a, $b) {
            $aSortKey = sprintf(
                '%s%s%s',
                $a->lpa->getDonor()->getSurname(),
                $a->lpa->getDonor()->getFirstname(),
                $a->lpa->getCaseSubtype()
            );

            $bSortKey = sprintf(
                '%s%s%s',
                $b->lpa->getDonor()->getSurname(),
                $b->lpa->getDonor()->getFirstname(),
                $b->lpa->getCaseSubtype()
            );

            if (0 === $cmp = strcmp($aSortKey, $bSortKey)) {
                $cmp = $a->added >= $b->added ? -1 : 1;
            }

            return $cmp;
        });

        return new ArrayObject($lpas, ArrayObject::ARRAY_AS_PROPS);
    }
}
