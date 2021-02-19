<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Service\Lpa\ValidateOlderLpaRequirements;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class ValidateOlderLpaRequirementsTest
 *
 * @package AppTest\Service\Lpa
 * @coversDefaultClass \App\Service\Lpa\ValidateOlderLpaRequirements
 */
class ValidateOlderLpaRequirementsTest extends TestCase
{
    /**
     * @test
     * @covers ::__invoke
     * @dataProvider registeredDataProvider
     *
     * @param array $lpa
     * @param bool $isValid
     *
     * @throws Exception
     */
    public function checks_the_lpa_is_registered_and_was_after_sep_2019(array $lpa, bool $isValid)
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $service = new ValidateOlderLpaRequirements($logger->reveal());

        $registrationValid = $service($lpa);
        $this->assertEquals($isValid, $registrationValid);
    }

    public function registeredDataProvider(): array
    {
        return [
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2021-01-01'
                ],
                true
            ],
            [
                [
                    'status' => 'Cancelled',
                    'registrationDate' => '2021-01-01'
                ],
                false
            ],
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2019-09-01'
                ],
                true
            ],
            [
                [
                    'status' => 'Registered',
                    'registrationDate' => '2019-08-31'
                ],
                false
            ],
            [
                [
                    'status' => 'Cancelled',
                    'registrationDate' => '2019-08-31'
                ],
                false
            ],
        ];
    }
}
