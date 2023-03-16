<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use Exception;
use Laminas\Stdlib\Exception\InvalidArgumentException;

class ParseLpaAlreadyAdded
{
    use BaselineValidData {
        BaselineValidData::isValidData as baseLineData;
    }

    /**
     * @param LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(private LpaFactory $lpaFactory)
    {
    }

    /**
     * @param array{donor: array, caseSubtype: string, lpaActorToken: string} $data
     * @return LpaAlreadyAdded
     * @throws Exception
     */
    public function __invoke(array $data): LpaAlreadyAdded
    {
        if (!$this->isValidData($data)) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        $response = new LpaAlreadyAdded();
        $response->setDonor($this->lpaFactory->createCaseActorFromData($data['donor']));
        $response->setCaseSubtype($data['caseSubtype']);
        $response->setLpaActorToken($data['lpaActorToken']);
        return $response;
    }

    /**
     * @param array{donor: array, caseSubtype: string, lpaActorToken: string} $data
     * @return bool
     */
    private function isValidData(array $data): bool
    {
        $baseLine         = $this->baseLineData($data);
        $hasLpaActorToken = isset($data['lpaActorToken']);

        if (
            !$baseLine ||
            !$hasLpaActorToken
        ) {
            return false;
        }

        return true;
    }
}
