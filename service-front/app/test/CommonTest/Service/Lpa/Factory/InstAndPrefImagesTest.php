<?php

declare(strict_types=1);

namespace CommonTest\Service\Lpa\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Service\Lpa\Factory\InstAndPrefImages;
use PHPUnit\Framework\TestCase;
use ValueError;
use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\SignedUrl;

#[CoversClass(InstAndPrefImages::class)]
#[CoversClass(Images::class)]
#[CoversClass(SignedUrl::class)]
class InstAndPrefImagesTest extends TestCase
{
    #[Test]
    public function it_creates_an_IAPImages(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => 'COLLECTION_NOT_STARTED',
            'signedUrls' => [],
        ];

        $factory = new InstAndPrefImages();

        $images = $factory->createFromData($data);

        $this->assertEquals($data['uId'], $images->uId);
    }

    #[Test]
    public function it_fails_with_a_bad_status(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => 'STATUS_DOES_NOT_EXIST',
            'signedUrls' => [],
        ];

        $factory = new InstAndPrefImages();

        $this->expectException(ValueError::class);
        $images = $factory->createFromData($data);
    }

    #[Test]
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

        $factory = new InstAndPrefImages();

        $images = $factory->createFromData($data);

        $this->assertEquals($data['uId'], $images->uId);
        $this->assertCount(1, $images->getInstructionsImageUrls());
        $this->assertCount(1, $images->getPreferencesImageUrls());
    }
}
