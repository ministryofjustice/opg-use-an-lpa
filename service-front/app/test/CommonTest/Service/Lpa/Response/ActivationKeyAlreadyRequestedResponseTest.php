<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Response;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use Common\Entity\CaseActor;
use Common\Service\Lpa\Response\ActivationKeyAlreadyRequested;
use PHPUnit\Framework\TestCase;

class ActivationKeyAlreadyRequestedResponseTest extends TestCase
{
    #[Test]
    public function it_can_create_a_response_dto(): void
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');

        $dto = new ActivationKeyAlreadyRequested(
            addedDate: '2021-11-17',
            donor: $donor,
            caseSubtype: 'pfa',
            activationKeyDueDate: '2021-12-06',
        );

        $this->assertInstanceOf(ActivationKeyAlreadyRequested::class, $dto);
        $this->assertEquals('2021-11-17', $dto->getAddedDate());
        $this->assertEquals($donor, $dto->getDonor());
        $this->assertEquals('pfa', $dto->getCaseSubtype());
        $this->assertEquals('2021-12-06', $dto->getDueDate());
    }
}
