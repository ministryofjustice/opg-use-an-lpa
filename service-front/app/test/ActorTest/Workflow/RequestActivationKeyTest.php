<?php

declare(strict_types=1);

namespace ActorTest\Workflow;

use Actor\Workflow\RequestActivationKey;
use DateTimeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Actor\Workflow\RequestActivationKey
 */
class RequestActivationKeyTest extends TestCase
{
    public function fullActivationKeyWorkflow(): array
    {
        return [
            [
                [
                    'firstNames'           => 'John Henry',
                    'lastName'             => 'Smith',
                    'dob'                  => '1955-11-05T00:00:00+00:00',
                    'postcode'             => 'PO3 6BT',
                    'referenceNumber'      => 700000000138,
                    'actorType'            => 'attorney',
                    'donorFirstNames'      => 'James Hubert',
                    'donorLastName'        => 'Swinton',
                    'donorDob'             => '1975-11-05T00:00:00+00:00',
                    'actorAddress1'        => '1 Example Street',
                    'actorAddress2'        => '',
                    'actorAddressTown'     => 'Portsmouth',
                    'actorAddressCounty'   => 'Hampshire',
                    'attorneyFirstNames'   => '',
                    'attorneyLastName'     => '',
                    'attorneyDob'          => null,
                    'addressOnPaper'       => '',
                    'telephone'            => '01234567890',
                    'noTelephone'          => false,
                    'actorUid'             => 700000000435,
                    'needsCleansing'       => false,
                    'actorAddressResponse' => 'Yes',
                ],
            ],
        ];
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function it_can_be_created_empty(): void
    {
        $sut = new RequestActivationKey();

        Assert::assertInstanceOf(RequestActivationKey::class, $sut);
    }

    /**
     * @test
     * @covers ::__construct
     * @dataProvider fullActivationKeyWorkflow
     */
    public function it_can_be_created_with_data(array $data): void
    {
        $sut = new RequestActivationKey(...$data);

        Assert::assertInstanceOf(RequestActivationKey::class, $sut);

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'dob':
                case 'donorDob':
                    Assert::assertInstanceOf(DateTimeInterface::class, $sut->$key);
                    Assert::assertEquals($value, $sut->$key->format('c'));
                    break;
                case 'attorneyDob':
                    Assert::assertNull($sut->$key);
                    break;
                case 'actorType':
                    Assert::assertEquals($data['actorType'], $sut->getActorRole());
                    break;
                case 'actorAddressResponse':
                    Assert::assertEquals($data['actorAddressResponse'], $sut->getActorAddressCheckResponse());
                    break;
                default:
                    Assert::assertEquals($value, $sut->$key);
            }
        }
    }

    /**
     * @test
     * @covers ::__construct
     * @covers ::reset
     * @dataProvider fullActivationKeyWorkflow
     */
    public function it_can_be_reset(array $data): void
    {
        $sut = new RequestActivationKey(...$data);

        Assert::assertInstanceOf(RequestActivationKey::class, $sut);

        $sut->reset();

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'firstNames':
                case 'lastName':
                case 'postcode':
                    Assert::assertEquals($value, $sut->$key);
                    break;
                case 'dob':
                    Assert::assertInstanceOf(DateTimeInterface::class, $sut->$key);
                    Assert::assertEquals($value, $sut->$key->format('c'));
                    break;
                case 'actorType':
                    Assert::assertNull($sut->getActorRole());
                    break;
                case 'actorAddressResponse':
                    Assert::assertNull($sut->getActorAddressCheckResponse());
                    break;
                case 'needsCleansing':
                    Assert::assertFalse($sut->$key);
                    break;
                default:
                    Assert::assertNull($sut->$key);
            }
        }
    }
}
