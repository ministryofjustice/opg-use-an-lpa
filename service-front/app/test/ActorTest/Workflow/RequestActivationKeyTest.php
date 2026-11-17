<?php

declare(strict_types=1);

namespace ActorTest\Workflow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Actor\Workflow\RequestActivationKey;
use DateTimeImmutable;
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
                    'firstNames'                  => 'John Henry',
                    'lastName'                    => 'Smith',
                    'dob'                         => '1955-11-05T00:00:00+00:00',
                    'postcode'                    => 'PO3 6BT',
                    'referenceNumber'             => '700000000138',
                    'actorType'                   => 'attorney',
                    'donorFirstNames'             => 'James Hubert',
                    'donorLastName'               => 'Swinton',
                    'donorDob'                    => '1975-11-05T00:00:00+00:00',
                    'actorAddress1'               => '1 Example Street',
                    'actorAddress2'               => '',
                    'actorAddressTown'            => 'Portsmouth',
                    'actorAddressCounty'          => 'Hampshire',
                    'attorneyFirstNames'          => '',
                    'attorneyLastName'            => '',
                    'attorneyDob'                 => null,
                    'addressOnPaper'              => '',
                    'telephone'                   => '01234567890',
                    'noTelephone'                 => false,
                    'actorUid'                    => '700000000435',
                    'needsCleansing'              => false,
                    'actorAddressResponse'        => 'Yes',
                    'liveInUK'                    => 'Yes',
                    'actorAbroadAddress'          => null,
                    'workflowPreviouslyCompleted' => true,
                    'checkAnswersReached'         => true,
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
                case 'workflowPreviouslyCompleted':
                case 'checkAnswersReached':
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
                case 'checkAnswersReached':
                    Assert::assertFalse($sut->$key);
                    break;
                case 'workflowPreviouslyCompleted':
                    Assert::assertTrue($sut->$key);
                    break;
                default:
                    Assert::assertNull($sut->$key);
            }
        }
    }

    private static function completedPersonalDetails(): array
    {
        return [
            'firstNames'                  => 'John Henry',
            'lastName'                    => 'Smith',
            'dob'                         => '1955-11-05T00:00:00+00:00',
            'postcode'                    => 'PO3 6BT',
            'liveInUK'                    => 'Yes',
            'workflowPreviouslyCompleted' => true,
        ];
    }

    #[Test]
    public function it_keeps_personal_details_when_the_same_name_is_given_after_a_completed_request(): void
    {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateName('John Henry', 'Smith');

        Assert::assertNotNull($sut->dob);
        Assert::assertSame('Yes', $sut->liveInUK);
        Assert::assertSame('PO3 6BT', $sut->postcode);
        Assert::assertTrue($sut->workflowPreviouslyCompleted);
    }

    public static function changedNames(): array
    {
        return [
            'first names changed' => [
                'Jane',
                'Smith',
            ],
            'last name changed'   => [
                'John Henry',
                'Jones',
            ],
        ];
    }

    #[DataProvider('changedNames')]
    #[Test]
    public function it_clears_personal_details_when_a_different_name_is_given_after_a_completed_request(
        string $firstNames,
        string $lastName,
    ): void {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateName($firstNames, $lastName);

        Assert::assertSame($firstNames, $sut->firstNames);
        Assert::assertSame($lastName, $sut->lastName);
        Assert::assertNull($sut->dob);
        Assert::assertNull($sut->liveInUK);
        Assert::assertNull($sut->postcode);
        Assert::assertFalse($sut->workflowPreviouslyCompleted);
    }

    #[Test]
    public function it_keeps_personal_details_when_the_name_is_changed_before_a_request_is_completed(): void
    {
        $sut = new RequestActivationKey(
            ...array_merge(self::completedPersonalDetails(), ['workflowPreviouslyCompleted' => null])
        );

        $sut->updateName('Jane', 'Jones');

        Assert::assertSame('Jane', $sut->firstNames);
        Assert::assertSame('PO3 6BT', $sut->postcode);
        Assert::assertNull($sut->workflowPreviouslyCompleted);
    }

    #[DataProvider('changedNames')]
    #[Test]
    public function it_keeps_personal_details_when_a_different_name_is_given_after_checking_answers(
        string $firstNames,
        string $lastName,
    ): void {
        $sut = new RequestActivationKey(
            ...array_merge(self::completedPersonalDetails(), ['checkAnswersReached' => true])
        );

        $sut->updateName($firstNames, $lastName);

        Assert::assertSame($firstNames, $sut->firstNames);
        Assert::assertSame($lastName, $sut->lastName);
        Assert::assertNotNull($sut->dob);
        Assert::assertSame('PO3 6BT', $sut->postcode);
        Assert::assertFalse($sut->workflowPreviouslyCompleted);
    }

    #[Test]
    public function it_still_treats_details_as_skipped_when_the_same_date_of_birth_is_given(): void
    {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateDob(new DateTimeImmutable('1955-11-05T14:30:00+00:00'));

        Assert::assertTrue($sut->workflowPreviouslyCompleted);
    }

    #[Test]
    public function it_no_longer_treats_details_as_skipped_when_a_different_date_of_birth_is_given(): void
    {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateDob(new DateTimeImmutable('1960-01-01'));

        Assert::assertSame('1960-01-01', $sut->dob->format('Y-m-d'));
        Assert::assertFalse($sut->workflowPreviouslyCompleted);
    }

    #[Test]
    public function it_still_treats_details_as_skipped_when_the_same_address_is_given(): void
    {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateAddress('Yes', 'PO3 6BT');

        Assert::assertTrue($sut->workflowPreviouslyCompleted);
    }

    public static function changedAddresses(): array
    {
        return [
            'postcode changed'      => [
                'Yes',
                'AB1 2CD',
            ],
            'no longer lives in UK' => [
                'No',
                null,
            ],
        ];
    }

    #[DataProvider('changedAddresses')]
    #[Test]
    public function it_no_longer_treats_details_as_skipped_when_a_different_address_is_given(
        string $liveInUK,
        ?string $postcode,
    ): void {
        $sut = new RequestActivationKey(...self::completedPersonalDetails());

        $sut->updateAddress($liveInUK, $postcode);

        Assert::assertSame($liveInUK, $sut->liveInUK);
        Assert::assertSame($postcode, $sut->postcode);
        Assert::assertFalse($sut->workflowPreviouslyCompleted);
    }
}
