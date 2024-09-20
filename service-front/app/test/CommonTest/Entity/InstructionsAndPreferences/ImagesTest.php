<?php

declare(strict_types=1);

namespace CommonTest\Entity\InstructionsAndPreferences;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Common\Entity\InstructionsAndPreferences\Images;
use Common\Entity\InstructionsAndPreferences\ImagesStatus;
use Common\Entity\InstructionsAndPreferences\SignedUrl;
use PHPUnit\Framework\TestCase;

#[CoversClass(Images::class)]
class ImagesTest extends TestCase
{
    #[Test]
    public function it_returns_requested_images(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => ImagesStatus::COLLECTION_COMPLETE,
            'signedUrls' => [
                new SignedUrl('iap-700000000001-instructions', 'http://instructions-image-url'),
                new SignedUrl('iap-700000000001-preferences', 'http://preferences-image-url'),
            ],
        ];

        $images = new Images(...$data);

        $signedUrls = $images->getInstructionsImageUrls();

        $this->assertCount(1, $signedUrls);
        $this->assertEquals('http://instructions-image-url', $signedUrls[0]->url);

        $signedUrls = $images->getPreferencesImageUrls();

        $this->assertCount(1, $signedUrls);
        $this->assertEquals('http://preferences-image-url', $signedUrls[0]->url);
    }

    #[Test]
    public function it_ignores_types_it_does_not_recognise(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => ImagesStatus::COLLECTION_COMPLETE,
            'signedUrls' => [
                new SignedUrl('iap-700000000001-instructions', 'http://instructions-image-url'),
                new SignedUrl(
                    'iap-700000000001-continuation_instructions_1',
                    'http://instructions-cont1-image-url'
                ),
                new SignedUrl(
                    'iap-700000000001-unknown_1',
                    'http://instructions-cont1-image-url'
                ),
            ],
        ];

        $images = new Images(...$data);

        $signedUrls = $images->getInstructionsImageUrls();

        $this->assertCount(2, $signedUrls);
        $this->assertEquals('http://instructions-image-url', $signedUrls[0]->url);
    }

    #[Test]
    public function it_implements_jsonserializable_correctly(): void
    {
        $data = [
            'uId'        => 700000000001,
            'status'     => ImagesStatus::COLLECTION_COMPLETE,
            'signedUrls' => [
                new SignedUrl('iap-700000000001-instructions', 'http://instructions-image-url'),
                new SignedUrl(
                    'iap-700000000001-continuation_instructions_1',
                    'http://instructions-cont1-image-url'
                ),
                new SignedUrl(
                    'iap-700000000001-unknown_1',
                    'http://unknown-cont1-image-url'
                ),
            ],
        ];

        $images = new Images(...$data);

        $json = json_encode($images);

        $this->assertJsonStringEqualsJsonString(
            '{"uId":700000000001,"status":"COLLECTION_COMPLETE","signedUrls":{"instructions":[{' .
            '"imageName":"iap-700000000001-instructions","url":"http:\/\/instructions-image-url"},{' .
            '"imageName":"iap-700000000001-continuation_instructions_1","url":"http:\/\/instructions-cont1-' .
            'image-url"}],"preferences":[],"unknown":[]}}',
            $json,
        );
    }
}
