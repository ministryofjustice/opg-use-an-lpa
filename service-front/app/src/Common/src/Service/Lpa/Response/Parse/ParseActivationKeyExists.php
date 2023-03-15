<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyExists;
use InvalidArgumentException;

class ParseActivationKeyExists
{
    /**
     * @param LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(private LpaFactory $lpaFactory)
    {
    }

    public function __invoke(array $data): ActivationKeyExists
    {
        if (
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

        $response = new ActivationKeyExists();
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);

        if (isset($data['activationKeyDueDate'])) {
            $response->setDueDate($data['activationKeyDueDate']);
        }

        return $response;
    }
}
