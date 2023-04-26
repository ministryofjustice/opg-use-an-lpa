<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyExists;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;

class ParseActivationKeyExists
{
    use BaselineValidData;

    /**
     * @param LpaFactory $lpaFactory
     * @codeCoverageIgnore
     */
    public function __construct(private LpaFactory $lpaFactory)
    {
    }

    /**
     * @param array{
     *     donor: array,
     *     caseSubtype: string,
     *     activationKeyDueDate: ?string,
     *     activationKeyRequestedDate: ?string
     * } $data
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
            $response->setDueDate(
                DateTimeImmutable::createFromFormat(
                    DateTimeInterface::ATOM,
                    $data['activationKeyDueDate']
                )
            );
        }

        if (isset($data['activationKeyRequestedDate'])) {
            $response->setRequestedDate(
                DateTimeImmutable::createFromFormat(
                    DateTimeInterface::ATOM,
                    $data['activationKeyRequestedDate']
                )
            );
        }

        return $response;
    }
}
