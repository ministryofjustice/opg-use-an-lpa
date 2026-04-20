<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa;

use Common\Entity\CaseActor;
use Common\Service\Lpa\AccessForAllApiResult;
use Common\Service\Lpa\Response\AccessForAllResult;
use Common\Service\Lpa\Response\ActivationKeyAlreadyRequested;
use Common\Service\Lpa\Response\ActivationKeyExists;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessForAllResult::class)]
class AccessForAllApiResultTest extends TestCase
{
    #[DataProvider('validDataTypeProvider')]
    #[Test]
    public function it_can_be_created_with_a_recognised_response_and_data_type($responseType, $additionalData): void
    {
        $response = new AccessForAllApiResult($responseType, $additionalData);
        $this->assertEquals($responseType, $response->getResponse());
        $this->assertEquals($additionalData, $response->getData());
    }

    private static function createAlreadyAddedDTO(): LpaAlreadyAdded
    {
        $dto = new LpaAlreadyAdded();
        $dto->setDonor(self::createDonor());
        $dto->setCaseSubtype('hw');
        $dto->setLpaActorToken('abc');
        return $dto;
    }

    private static function createActivationKeyExistsDTO(): ActivationKeyExists
    {
        $dto = new ActivationKeyExists();
        $dto->setDonor(self::createDonor());
        $dto->setCaseSubtype('pfa');
        return $dto;
    }

    private static function createDonor(): CaseActor
    {
        $donor = new CaseActor();
        $donor->setUId('12345');
        $donor->setFirstname('Example');
        $donor->setMiddlenames('Donor');
        $donor->setSurname('Person');
        return $donor;
    }

    /**
     * @return array
     */
    public static function validDataTypeProvider()
    {
        return [
            [
                AccessForAllResult::LPA_ALREADY_ADDED,
                self::createAlreadyAddedDTO(),
            ],
            [
                AccessForAllResult::HAS_ACTIVATION_KEY,
                self::createActivationKeyExistsDTO(),
            ],
            [
                AccessForAllResult::KEY_ALREADY_REQUESTED,
                new ActivationKeyAlreadyRequested(
                    donor: self::createDonor(),
                    caseSubtype: 'pfa',
                    addedDate: '2020-01-02',
                    activationKeyDueDate: '2020-01-17',
                ),
            ],
            [
                AccessForAllResult::DOES_NOT_MATCH,
                [],
            ],
            [
                AccessForAllResult::NOT_ELIGIBLE,
                [],
            ],
            [
                AccessForAllResult::NOT_FOUND,
                [],
            ],
            [
                AccessForAllResult::SUCCESS,
                [],
            ],
            [
                AccessForAllResult::OLDER_LPA_NEEDS_CLEANSING,
                [],
            ],
            [
                AccessForAllResult::POSTCODE_NOT_SUPPLIED,
                [],
            ],
            [
                AccessForAllResult::STATUS_NOT_VALID,
                [],
            ],
        ];
    }

    #[Test]
    public function it_makes_available_the_type_and_passed_in_additional_data(): void
    {
        $data = [
            'test' => 'data',
        ];

        $sut = new AccessForAllApiResult(AccessForAllResult::SUCCESS, $data);

        $this->assertEquals(AccessForAllResult::SUCCESS, $sut->getResponse());
        $this->assertEquals($data, $sut->getData());
    }
}
