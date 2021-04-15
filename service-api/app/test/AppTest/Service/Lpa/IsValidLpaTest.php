<?php

namespace AppTest\Service\Lpa;

use PHPUnit\Framework\TestCase;
use App\Service\Lpa\IsValidLpa;
use Psr\Log\LoggerInterface;

class IsValidLpaTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerProphecy;

    public function setUp()
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
            'status' => 'Registered',
            'donor' => [
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
            'status' => 'Cancelled',
            'donor' => [
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
            'status' => 'Revoked',
            'donor' => [
                'id' => 1,
            ]
        ];

        $resolver = $this->isValidLpaResolver();
        $result = $resolver($lpa);
        $this->assertFalse($result);
    }
}
