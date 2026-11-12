<?php

declare(strict_types=1);

namespace ActorTest\Workflow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Actor\Workflow\RequestActivationKey;
use DateTimeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestActivationKey::class)]
class RequestActivationKeyTest extends TestCase
{
    public static function fullActivationKeyWorkflow(): array
    {
        return [
            [
                [
                    'firstNames'           => 'John Henry',
                    'lastName'             => 'Smith',
                    'dob'                  => '1955-11-05T00:00:00+00:00',
                    'postcode'             => 'PO3 6BT',
                    'referenceNumber'      => '700000000138',
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
                    'actorUid'             => '700000000435',
                    'needsCleansing'       => false,
                    'actorAddressResponse' => 'Yes',
                    'liveInUK'             => 'Yes',
                    'actorAbroadAddress'   => null,
                    'requestingAnother'    => true,
                ],
            ],
        ];
    }

    #[Test]
    public function it_can_be_created_empty(): void
    {
        $sut = new RequestActivationKey();

        Assert::assertInstanceOf(RequestActivationKey::class, $sut);
    }

    #[DataProvider('fullActivationKeyWorkflow')]
    #[Test]
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
                    Assert::assertEquals($data['actorType'], $sut->getActorRole()->value);
                    break;
                case 'actorAddressResponse':
                    Assert::assertEquals($data['actorAddressResponse'], $sut->getActorAddressResponse()->value);
                    break;
                default:
                    Assert::assertEquals($value, $sut->$key);
            }
        }
    }

    #[DataProvider('fullActivationKeyWorkflow')]
    #[Test]
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
                case 'dob':
                    Assert::assertNull($sut->$key);
                    break;
                case 'actorType':
                    Assert::assertNull($sut->getActorRole());
                    break;
                case 'actorAddressResponse':
                    Assert::assertNull($sut->getActorAddressResponse());
                    break;
                case 'needsCleansing':
                case 'requestingAnother':
                    Assert::assertFalse($sut->$key);
                    break;
                default:
                    Assert::assertNull($sut->$key);
            }
        }
    }

    #[DataProvider('fullActivationKeyWorkflow')]
    #[Test]
    public function it_can_be_reset_for_another_request_keeping_personal_details(array $data): void
    {
        $data['requestingAnother'] = null;

        $sut = new RequestActivationKey(...$data);

        $sut->resetForAnotherRequest();

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'firstNames':
                case 'lastName':
                case 'postcode':
                case 'liveInUK':
                    Assert::assertEquals($value, $sut->$key);
                    break;
                case 'dob':
                    Assert::assertEquals($value, $sut->$key->format('c'));
                    break;
                case 'actorType':
                    Assert::assertNull($sut->getActorRole());
                    break;
                case 'actorAddressResponse':
                    Assert::assertNull($sut->getActorAddressResponse());
                    break;
                case 'needsCleansing':
                    Assert::assertFalse($sut->$key);
                    break;
                case 'requestingAnother':
                    Assert::assertTrue($sut->$key);
                    break;
                default:
                    Assert::assertNull($sut->$key);
            }
        }

        Assert::assertTrue($sut->hasPersonalDetails());
    }

    public static function personalDetails(): array
    {
        $complete = [
            'firstNames' => 'John Henry',
            'lastName'   => 'Smith',
            'dob'        => '1955-11-05T00:00:00+00:00',
            'postcode'   => 'PO3 6BT',
            'liveInUK'   => 'Yes',
        ];

        return [
            'all details present'          => [$complete, true],
            'lives abroad with no postcode' => [array_merge($complete, ['postcode' => '', 'liveInUK' => 'No']), true],
            'missing first names'          => [array_merge($complete, ['firstNames' => null]), false],
            'missing last name'            => [array_merge($complete, ['lastName' => null]), false],
            'missing date of birth'        => [array_merge($complete, ['dob' => null]), false],
            'missing postcode'             => [array_merge($complete, ['postcode' => null]), false],
            'missing live in UK answer'    => [array_merge($complete, ['liveInUK' => null]), false],
            'no details at all'            => [[], false],
        ];
    }

    #[DataProvider('personalDetails')]
    #[Test]
    public function it_knows_if_it_has_all_personal_details(array $data, bool $expected): void
    {
        $sut = new RequestActivationKey(...$data);

        Assert::assertSame($expected, $sut->hasPersonalDetails());
    }
}
