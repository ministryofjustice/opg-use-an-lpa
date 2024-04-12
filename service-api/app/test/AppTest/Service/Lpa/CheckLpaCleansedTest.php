<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\Response\Lpa;
use App\Exception\BadRequestException;
use App\Service\Lpa\CheckLpaCleansed;
use App\Service\Lpa\LpaService;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(CheckLpaCleansed::class)]
class CheckLpaCleansedTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;
    private LpaService|ObjectProphecy $lpaServiceProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy     = $this->prophesize(LoggerInterface::class);
        $this->lpaServiceProphecy = $this->prophesize(LpaService::class);
    }

    public function checkLpaCleansed(): CheckLpaCleansed
    {
        return new CheckLpaCleansed(
            $this->loggerProphecy->reveal(),
            $this->lpaServiceProphecy->reveal(),
        );
    }

    #[Test]
    public function older_lpa_add_confirmation_throws_an_exception_if_lpa_not_cleansed_and_registered_before_sep2019(): void
    {
        $userId = '1234';
        $lpa    = new Lpa(
            [
                'registrationDate' => '2018-05-26',
                'lpaIsCleansed'    => false,
            ],
            new DateTime()
        );

        $actorDetailsMatch = [
            'lpa-id' => '700000000001',
            'actor'  => [
                'uId' => '700000000002',
            ],
        ];

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch['lpa-id'])
            ->willReturn($lpa);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('LPA needs cleansing');

        $this->checkLpaCleansed()($userId, $actorDetailsMatch);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_cleansed_lpa_and_registered_before_sep2019(): void
    {
        $userId = '1234';
        $lpa    = new Lpa(
            [
                'registrationDate' => '2018-05-26',
                'lpaIsCleansed'    => true,
            ],
            new DateTime()
        );

        $actorDetailsMatch = [
            'lpa-id' => '700000000001',
        ];

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch['lpa-id'])
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_a_lpa_not_cleansed_and_registered_after_sep2019(): void
    {
        $userId = '1234';
        $lpa    = new Lpa(
            [
                'registrationDate' => '2019-09-01',
                'lpaIsCleansed'    => false,
            ],
            new DateTime()
        );

        $actorDetailsMatch = [
            'lpa-id' => '700000000001',
        ];

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch['lpa-id'])
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }

    #[Test]
    public function older_lpa_add_confirmation_accepts_an_lpa_cleansed_and_registered_after_sep2019(): void
    {
        $userId = '1234';
        $lpa    = new Lpa(
            [
                'registrationDate' => '2019-09-01',
                'lpaIsCleansed'    => true,
            ],
            new DateTime()
        );

        $actorDetailsMatch = [
            'lpa-id' => '700000000001',
        ];

        $this->lpaServiceProphecy
            ->getByUid($actorDetailsMatch['lpa-id'])
            ->willReturn($lpa);

        $result = $this->checkLpaCleansed()($userId, $actorDetailsMatch);
        $this->assertNull($result);
    }
}
