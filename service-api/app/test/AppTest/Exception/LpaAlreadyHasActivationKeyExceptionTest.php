<?php

declare(strict_types=1);

namespace AppTest\Exception;

use App\Entity\Sirius\SiriusLpaDonor;
use App\Exception\LpaAlreadyHasActivationKeyException;
use App\Service\Lpa\SiriusLpaManager;
use App\Service\Lpa\SiriusPerson;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class LpaAlreadyHasActivationKeyExceptionTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;


    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }


    #[Test]
    public function it_can_be_instantiated(): void
    {
        $additionalData = [
            'some' => 'additional',
            'data' => 'here,',
        ];

        $sut = new LpaAlreadyHasActivationKeyException($additionalData);

        $this->assertEquals('Bad Request', $sut->getTitle());
        $this->assertEquals($additionalData, $sut->getAdditionalData());

        $this->assertEquals('LPA has an activation key already', $sut->getMessage());
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $sut->getCode());
    }

    #[Test]
    public function it_narrows_scope_of_logged_data(): void
    {
        $additionalData = [
            'donor' => new SiriusPerson([
                'uId' => '700000000047',
                ],
            $this->loggerProphecy->reveal()
            ),
            'caseSubtype'          => 'pfa',
            'activationKeyDueDate' => '2020-12-31',
            'some'                 => 'additional',
            'data'                 => 'here,',
        ];

        $sut = new LpaAlreadyHasActivationKeyException($additionalData);

        $this->assertEquals(
            [
                'donor'                => [
                    'uId' => '700000000047',
                ],
                'caseSubtype'          => 'pfa',
                'activationKeyDueDate' => '2020-12-31',
            ],
            $sut->getAdditionalDataForLogging(),
        );
    }

    #[Test]
    public function it_narrows_scope_of_logged_data_combined_format(): void
    {
        $additionalData = [
            'donor' => new SiriusLpaDonor(
                addressLine1: null,
                addressLine2: null,
                addressLine3: null,
                country:      null,
                county:       null,
                dob:          null,
                email:        null,
                firstname:    null,
                id:           null,
                linked:       null,
                middlenames:  null,
                otherNames:   null,
                postcode:     null,
                surname:      null,
                systemStatus: null,
                town:         null,
                uId:          '700000000047',
            ),
            'caseSubtype'          => 'pfa',
            'activationKeyDueDate' => '2020-12-31',
            'some'                 => 'additional',
            'data'                 => 'here,',
        ];

        $sut = new LpaAlreadyHasActivationKeyException($additionalData);

        $this->assertEquals(
            [
                'donor'                => [
                    'uId' => '700000000047',
                ],
                'caseSubtype'          => 'pfa',
                'activationKeyDueDate' => '2020-12-31',
            ],
            $sut->getAdditionalDataForLogging(),
        );
    }
}
