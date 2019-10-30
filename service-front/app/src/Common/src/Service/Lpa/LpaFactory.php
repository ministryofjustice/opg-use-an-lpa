<?php

declare(strict_types=1);

namespace Common\Service\Lpa;

use Common\Entity\Address;
use Common\Entity\CaseActor;
use Common\Entity\Lpa;
use Exception;
use InvalidArgumentException;

interface LpaFactory
{
    /**
     * Creates a Lpa from the supplied data array.
     *
     * @param array $data
     * @return Lpa
     * @throws InvalidArgumentException|Exception
     */
    public function createLpaFromData(array $data) : Lpa;

    /**
     * @param array $caseActorData
     * @return CaseActor
     * @throws InvalidArgumentException|Exception
     */
    public function createCaseActorFromData(array $caseActorData) : CaseActor;

    /**
     * @param array $addressData
     * @return Address
     * @throws InvalidArgumentException
     */
    public function createAddressFromData(array $addressData) : Address;
}