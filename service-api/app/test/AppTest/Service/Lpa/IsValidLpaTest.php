<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\IsValidLpa;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class IsValidLpaTest extends TestCase
{
    use ProphecyTrait;

    private LoggerInterface|ObjectProphecy $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy     = $this->prophesize(LoggerInterface::class);
        $this->isValidLpaProphecy = $this->prophesize(IsValidLpa::class);
    }

    private function isValidLpaResolver(): IsValidLpa
    {
        return new IsValidLpa(
            $this->loggerProphecy->reveal()
        );
    }

    #[Test]
    public function check_if_lpa_valid_when_status_registered(): void
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Registered',
            'donor'  => [
                'id' => 1,
            ],
        ];

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_cancelled(): void
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Cancelled',
            'donor'  => [
                'id' => 1,
            ],
        ];

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertTrue($result);
    }

    #[Test]
    public function check_if_lpa_valid_when_status_other_than_registered_or_cancelled(): void
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Revoked',
            'donor'  => [
                'id' => 1,
            ],
        ];

        $resolver = $this->isValidLpaResolver();
        $result   = $resolver($lpa);
        $this->assertFalse($result);
    }
}
