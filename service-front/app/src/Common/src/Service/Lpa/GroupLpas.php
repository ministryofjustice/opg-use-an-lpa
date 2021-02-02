<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use ArrayObject;

/**
 * Class GroupLpas
 *
 * Single action invokeable that groups incoming LPA array objects according to our criteria.
 * Groups LPAs by donor, separating incorrect matches by DoB
 *
 * @package Common\Service\Lpa
 */
class GroupLpas
{
    /**
     * Groups LPAs based on name and DoB
     *
     * @param ArrayObject $lpas
     *
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
                        $lpa->lpa->getDonor()->getFirstname(),
                        $lpa->lpa->getDonor()->getMiddlenames(),
                        $lpa->lpa->getDonor()->getSurname(),
                        // prevents different donors with same name from being grouped together
                        $lpa->lpa->getDonor()->getDob()->format('Y-m-d')
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
