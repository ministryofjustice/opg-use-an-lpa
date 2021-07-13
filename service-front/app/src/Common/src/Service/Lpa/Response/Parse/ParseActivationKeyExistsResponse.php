<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyExistsResponse;
use InvalidArgumentException;

class ParseActivationKeyExistsResponse
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

    public function __invoke(array $data): ActivationKeyExistsResponse
    {
        if (
            !isset($data['donor']['uId']) ||
            !isset($data['donor']['firstname']) ||
            !isset($data['donor']['middlenames']) ||
            !isset($data['donor']['surname']) ||
            !isset($data['caseSubtype'])
        ) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new ActivationKeyExistsResponse();
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);
        return $response;
    }
}
