<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\OlderLpaMatchResponse;
use InvalidArgumentException;

class ParseOlderLpaMatchResponse
{
    /** @var LpaFactory */
    private LpaFactory $lpaFactory;

    /**
     * @param LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(LpaFactory $lpaFactory)
    {
        $this->lpaFactory = $lpaFactory;
    }

    public function __invoke(array $data): OlderLpaMatchResponse
    {
        if (
            // if the actor is the donor then the attorney data wont exist
            !isset($data['donor']['uId']) ||
            !array_key_exists('firstname', $data['donor']) ||
            !array_key_exists('middlenames', $data['donor']) ||
            !array_key_exists('surname', $data['donor']) ||
            !isset($data['caseSubtype'])
        ) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new OlderLpaMatchResponse();

        if (array_key_exists('attorney', $data)) {
            $response->setAttorney($this->lpaFactory->createCaseActorFromData($data['attorney']));
        }
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);
        $response->setLpaCleansed($data['lpaIsCleansed']);

        return $response;
    }
}
