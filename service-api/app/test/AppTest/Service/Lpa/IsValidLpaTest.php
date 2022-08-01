<?php

namespace AppTest\Service\Lpa;

use App\Service\Lpa\IsValidLpa;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IsValidLpaTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp(): void
    {
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->isValidLpaProphecy = $this->prophesize(IsValidLpa::class);
    }

    private function isValidLpaResolver(): IsValidLpa
    {
        return new IsValidLpa(
            $this->loggerProphecy->reveal()
        );
    }

    /** @test */
    public function check_if_lpa_valid_when_status_registered()
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Registered',
            'donor'  => [
                'id' => 1,
            ]
        ];

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertTrue($result);
    }

    /** @test */
    public function check_if_lpa_valid_when_status_cancelled()
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Cancelled',
            'donor'  => [
                'id' => 1,
            ]
        ];

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertTrue($result);
    }

    /** @test */
    public function check_if_lpa_valid_when_status_other_than_registered_or_cancelled()
    {
        $lpa = [
            'uId'    => '700000000001',
            'status' => 'Revoked',
            'donor'  => [
                'id' => 1,
            ]
        ];

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertFalse($result);
    }
}
