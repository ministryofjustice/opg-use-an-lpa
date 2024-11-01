<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Single action invokeable that groups incoming LPA array objects according to our criteria.
 * Groups LPAs by donor, separating incorrect matches by DoB
 */
class GroupLpas
{
    /**
     * Groups LPAs based on name and DoB
     *
     * @param ArrayObject $lpas
     * @return ArrayObject
     */
    public function __invoke(ArrayObject $lpas): ArrayObject
    {
        $lpas = $lpas->getArrayCopy();

        $donors = [];
        foreach ($lpas as $userLpaToken => $lpa) {
            $donor = implode(
                ' ',
                array_filter(
                    [
                        $lpa->lpa->donor->firstname,
                        $lpa->lpa->donor->otherNames,
                        $lpa->lpa->donor->surname,
                        // prevents different donors with same name from being grouped together
                        $lpa->lpa->donor->dob ? $lpa->lpa->donot->dob->format('Y-m-d') : '0-0-0',
                    ]
                )
            );

            if (array_key_exists($donor, $donors)) {
                $donors[$donor][$userLpaToken] = $lpa;
            } else {
                $donors[$donor] = [$userLpaToken => $lpa];
            }
        }

        return new ArrayObject($donors, ArrayObject::ARRAY_AS_PROPS);
    }
}
