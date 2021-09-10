<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\Lpa\ValidateOlderLpaRequirements;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use App\Service\Features\FeatureEnabled;

/**
 * Class ValidateOlderLpaRequirementsTest
 *
 * @package AppTest\Service\Lpa
 * @coversDefaultClass \App\Service\Lpa\ValidateOlderLpaRequirements
 */
class ValidateOlderLpaRequirementsTest extends TestCase
{
    /** @var ObjectProphecy|LoggerInterface */
    private $loggerProphecy;

    /** @var ObjectProphecy|FeatureEnabled */
    private $featureEnabledProphecy;

    public function setUp()
    {
        $this->featureEnabledProphecy = $this->prophesize(FeatureEnabled::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
    }

    public function validateLpaRequirements(): ValidateOlderLpaRequirements
    {
        return new ValidateOlderLpaRequirements(
            $this->loggerProphecy->reveal(),
            $this->featureEnabledProphecy->reveal(),
        );
    }

     /**
     * @test
     * @throws Exception
     */
    public function throws_not_found_exception_when_lpa_status_is_not_registered()
    {
        $this->featureEnabledProphecy->__invoke('allow_older_lpas')->willReturn(true);

        $lpa = [
            'status' => 'Pending',
        ];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA status invalid');

        ($this->validateLpaRequirements()($lpa));
    }

    /**
     * @test
     * @throws Exception
     */
    public function throws_bad_request_exception_when_lpa_registration_date_before_Sep_2019()
    {
        $this->featureEnabledProphecy->__invoke('allow_older_lpas')->willReturn(false);

        $lpa = [
            'status'            => 'Registered',
            'registrationDate'  => '2019-08-31',
        ];

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('PA not eligible due to registration date');

        ($this->validateLpaRequirements()($lpa));
    }

    /**
     * @test
     * @throws Exception
     */
    public function throws_bad_request_exception_when_lpa_status_is_pending_and_registration_date_after_Sep_2019()
    {
        $this->featureEnabledProphecy->__invoke('allow_older_lpas')->willReturn(false);

        $lpa = [
            'status'            => 'Pending',
            'registrationDate'  => '2019-09-31',
        ];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA status invalid');

        ($this->validateLpaRequirements()($lpa));
    }

    /**
     * @test
     * @throws Exception
     */
    public function when_allow_older_lpa_flag_on_ignores_checking_registration_date_but_only_status()
    {
        $this->featureEnabledProphecy->__invoke('allow_older_lpas')->willReturn(true);

        $lpa = [
            'status'            => 'Pending',
            'registrationDate'  => '2019-08-31',
        ];

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('LPA status invalid');

        ($this->validateLpaRequirements()($lpa));
    }
}

