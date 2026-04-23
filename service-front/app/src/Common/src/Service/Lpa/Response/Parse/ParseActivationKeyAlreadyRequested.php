<?php

declare(strict_types=1);

namespace Common\Service\Lpa\Response\Parse;

use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\Response\ActivationKeyAlreadyRequested;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

class ParseActivationKeyAlreadyRequested
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
     *     donor: array{
     *         uId: string,
     *         firstnames: string,
     *         surname: string,
     *     },
     *     caseSubtype: string,
     *     addedDate: string,
     *     activationKeyDueDate: string
     * } $data
     * @throws Exception
     */
    public function __invoke(array $data): ActivationKeyAlreadyRequested
    {
        if (!$this->isValidData($data)) {
            throw new InvalidArgumentException(
                'The data array passed to ' . __METHOD__ . ' does not contain the required fields'
            );
        }

        return new ActivationKeyAlreadyRequested(
            addedDate: $data['addedDate'],
            donor: $this->lpaFactory->createCaseActorFromData($data['donor']),
            caseSubtype: $data['caseSubtype'],
            activationKeyDueDate: $data['activationKeyDueDate'],
        );
    }
}
