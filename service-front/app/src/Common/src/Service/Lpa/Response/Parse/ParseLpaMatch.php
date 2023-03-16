<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\LpaMatch;
use Exception;
use InvalidArgumentException;

class ParseLpaMatch
{
    /**
     * @param LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(private LpaFactory $lpaFactory)
    {
    }

    /**
     * @param array{donor: array, caseSubtype: string} $data
     * @return LpaMatch
     * @throws Exception
     */
    public function __invoke(array $data): LpaMatch
    {
        if (!$this->isValidData($data)) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new LpaMatch();

        if (array_key_exists('attorney', $data)) {
            $response->setAttorney($this->lpaFactory->createCaseActorFromData($data['attorney']));
        }
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);

        return $response;
    }

    /**
     * @param array{donor: array, caseSubtype: string, lpaActorToken: string} $data
     * @return bool
     */
    private function isValidData(array $data): bool
    {
        if (
            // if the actor is the donor then the attorney data wont exist
            !isset($data['donor']['uId']) ||
            !array_key_exists('firstname', $data['donor']) ||
            !array_key_exists('middlenames', $data['donor']) ||
            !array_key_exists('surname', $data['donor']) ||
            !isset($data['caseSubtype'])
        ) {
            return false;
        }

        return true;
    }
}
