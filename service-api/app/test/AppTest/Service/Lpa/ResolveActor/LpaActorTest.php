<?php

declare(strict_types=1);

namespace AppTest\Service\Lpa\ResolveActor;

use App\Service\Lpa\ResolveActor\ActorType;
use App\Service\Lpa\ResolveActor\LpaActor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LpaActorTest extends TestCase
{
    #[Test]
    public function serializes_as_expected(): void
    {
        $sut = new LpaActor(
            [
                'firstName' => 'John',
                'lastName'  => 'Doe',
            ],
            ActorType::ATTORNEY,
        );

        $jsonString = '{"details":{"firstName":"John","lastName":"Doe"},"type":"attorney"}';
        $this->assertJsonStringEqualsJsonString($jsonString, json_encode($sut));
    }
}
