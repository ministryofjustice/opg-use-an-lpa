<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Single action invokeable that sorts incoming LPA array objects according to our criteria.
 * Sort order starts with Surname and uses Firstname and case subtypes to further sort.
 */
class SortLpas
{
    /**
     * Sorts the two LPAs by Surname, forename, case-subtype and finally the added date.
     *
     * @param ArrayObject $lpas
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas): ArrayObject
    {
        $lpas = $lpas->getArrayCopy();

        uasort($lpas, function ($a, $b) {
            $aSortKey = sprintf(
                '%s%s%s',
                $a->lpa->donor->surname,
                $a->lpa->donor->firstname,
                $a->lpa->caseSubtype->value
            );

            $bSortKey = sprintf(
                '%s%s%s',
                $b->lpa->donor->surname,
                $b->lpa->donor->firstname,
                $b->lpa->caseSubtype->value
            );

            if (0 === $cmp = strcmp($aSortKey, $bSortKey)) {
                $cmp = $a->added >= $b->added ? -1 : 1;
            }

            return $cmp;
        });

        return new ArrayObject($lpas, ArrayObject::ARRAY_AS_PROPS);
    }
}
