<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa;

use App\DataAccess\Repository\InstructionsAndPreferencesImagesInterface;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImages;
use App\DataAccess\Repository\Response\InstructionsAndPreferencesImagesResult;
use App\Service\Lpa\GetInstructionsAndPreferencesImages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetInstructionsAndPreferencesImages::class)]
class GetInstructionsAndPreferencesImagesTest extends TestCase
{
    #[Test]
    public function it_can_be_initialised(): void
    {
        $repositoryStub = $this->createStub(InstructionsAndPreferencesImagesInterface::class);

        $sut = new GetInstructionsAndPreferencesImages($repositoryStub);

        $this->assertInstanceOf(GetInstructionsAndPreferencesImages::class, $sut);
    }

    #[Test]
    public function it_returns_images_when_passed_a_uid(): void
    {
        $images = new InstructionsAndPreferencesImages(
            700000000001,
            InstructionsAndPreferencesImagesResult::COLLECTION_COMPLETE,
            [
                'iap-700000000001-instructions' => 'https://image-url',
            ],
        );

        $repositoryMock = $this->createMock(InstructionsAndPreferencesImagesInterface::class);
        $repositoryMock
            ->expects($this->once())
            ->method('getInstructionsAndPreferencesImages')
            ->with($this->equalTo(700000000001))
            ->willReturn($images);

        $sut = new GetInstructionsAndPreferencesImages($repositoryMock);

        $result = ($sut)(700000000001);

        $this->assertEquals($images, $result);
    }
}
