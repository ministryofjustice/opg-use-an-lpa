<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

trait BaselineValidData
{
    /**
     * @param array{
     *     donor: array{
     *         uId: string,
     *         firstnames: string,
     *         surname: string,
     *     },
     *     attorney?: array{
     *         uId: string,
     *         firstnames: string,
     *         surname: string,
     *     },
     *     caseSubtype: string,
     *     activationKeyDueDate?: string
     * } $data
 * @return bool
     */
    private function isValidData(array $data): bool
    {
        $hasDonor = isset($data['donor']['uId']);

        if (!$hasDonor) {
            return false;
        }

        $hasDonorName   = array_key_exists('firstnames', $data['donor'])
            && array_key_exists('surname', $data['donor']);
        $hasCaseSubType = isset($data['caseSubtype']);

        if (!$hasDonorName || !$hasCaseSubType) {
            return false;
        }

        return true;
    }
}
