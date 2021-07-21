<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\LpaAlreadyAddedResponse;
use Laminas\Stdlib\Exception\InvalidArgumentException;

class ParseLpaAlreadyAddedResponse
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

    public function __invoke(array $data): LpaAlreadyAddedResponse
    {
        if (
            !isset($data['donor']['uId']) ||
            !array_key_exists('firstname', $data['donor']) ||
            !array_key_exists('middlenames', $data['donor']) ||
            !array_key_exists('surname', $data['donor']) ||
            !isset($data['caseSubtype']) ||
            !isset($data['lpaActorToken'])
        ) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new LpaAlreadyAddedResponse();
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);
        $response->setLpaActorToken($data['lpaActorToken']);
        return $response;
    }
}
