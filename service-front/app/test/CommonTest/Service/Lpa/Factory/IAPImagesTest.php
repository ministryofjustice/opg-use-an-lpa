<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Factory;

use Common\Service\Lpa\Factory\IAPImages;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * @coversDefaultClass \Common\Service\Lpa\Factory\IAPImages
 */
class IAPImagesTest extends TestCase
{
    /**
     * @test
     * @covers ::createFromData
     * @covers \Common\Entity\InstructionsAndPreferences\Images::__construct
     */
    public function it_creates_an_IAPImages(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => 'COLLECTION_NOT_STARTED',
            'signedUrls' => [],
        ];

        $factory = new IAPImages();

        $images = $factory->createFromData($data);

        $this->assertEquals($data['uId'], $images->uId);
    }

    /**
     * @test
     * @covers ::createFromData
     * @covers \Common\Entity\InstructionsAndPreferences\Images::__construct
     */
    public function it_fails_with_a_bad_status(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => 'STATUS_DOES_NOT_EXIST',
            'signedUrls' => [],
        ];

        $factory = new IAPImages();

        $this->expectException(ValueError::class);
        $images = $factory->createFromData($data);
    }

    /**
     * @test
     * @covers ::createFromData
     * @covers \Common\Entity\InstructionsAndPreferences\Images::__construct
     * @covers \Common\Entity\InstructionsAndPreferences\SignedUrl::__construct
     */
    public function it_creates_an_IAPImages_with_urls(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => 'COLLECTION_NOT_STARTED',
            'signedUrls' => [
                'iap-700000000001-instructions' => 'http://image-url',
                'iap-700000000001-preferences'  => 'http://image-url',
            ],
        ];

        $factory = new IAPImages();

        $images = $factory->createFromData($data);

        $this->assertEquals($data['uId'], $images->uId);
        $this->assertCount(1, $images->getInstructionsImageUrls());
        $this->assertCount(1, $images->getPreferencesImageUrls());
    }
}
