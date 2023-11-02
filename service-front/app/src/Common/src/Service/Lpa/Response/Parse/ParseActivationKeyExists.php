<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyExists;
use Exception;
use InvalidArgumentException;

class ParseActivationKeyExists
{
    use BaselineValidData;

    /**
     * @param              LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(private LpaFactory $lpaFactory)
    {
    }

    /**
     * @param  array{donor: array, caseSubtype: string} $data
     * @return ActivationKeyExists
     * @throws Exception
     */
    public function __invoke(array $data): ActivationKeyExists
    {
        if (!$this->isValidData($data)) {
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
