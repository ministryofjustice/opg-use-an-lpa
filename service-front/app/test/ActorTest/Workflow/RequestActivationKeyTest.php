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
                    'visitedPages'                => [
                        'lpa.add-by-paper',
                        'lpa.your-name',
                    ],
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

    #[Test]
    public function it_has_no_previous_page_until_a_second_page_is_visited(): void
    {
        $sut = new RequestActivationKey();
        Assert::assertNull($sut->previousPage());

        $sut->visitPage('lpa.add-by-paper');
        Assert::assertNull($sut->previousPage());

        $sut->visitPage('lpa.your-name');
        Assert::assertSame('lpa.add-by-paper', $sut->previousPage());
    }

    #[Test]
    public function it_follows_the_route_taken_when_going_back(): void
    {
        $sut   = new RequestActivationKey();
        $route = [
            'lpa.add-by-paper',
            'lpa.your-name',
            'lpa.date-of-birth',
            'lpa.postcode',
            'lpa.check-answers',
        ];
        foreach ($route as $page) {
            $sut->visitPage($page);
        }

        $sut->visitPage('lpa.postcode');
        Assert::assertSame('lpa.date-of-birth', $sut->previousPage());

        $sut->visitPage('lpa.date-of-birth');
        Assert::assertSame('lpa.your-name', $sut->previousPage());
    }

    #[Test]
    public function it_returns_to_the_page_a_change_was_made_from(): void
    {
        $sut = new RequestActivationKey(
            visitedPages: [
                'lpa.add-by-paper',
                'lpa.your-name',
                'lpa.date-of-birth',
                'lpa.postcode',
                'lpa.check-answers',
            ],
        );

        $sut->visitPage('lpa.date-of-birth');
        Assert::assertSame('lpa.check-answers', $sut->previousPage());

        $sut->visitPage('lpa.check-answers');
        Assert::assertSame('lpa.postcode', $sut->previousPage());
    }

    #[Test]
    public function it_ignores_a_repeat_visit_to_the_current_page(): void
    {
        $sut = new RequestActivationKey(visitedPages: ['lpa.add-by-paper', 'lpa.your-name']);

        $sut->visitPage('lpa.your-name');

        Assert::assertSame(['lpa.add-by-paper', 'lpa.your-name'], $sut->visitedPages);
    }

    #[Test]
    public function it_limits_the_number_of_pages_remembered(): void
    {
        $sut = new RequestActivationKey();

        for ($i = 0; $i < 12; $i++) {
            $sut->visitPage('page-' . $i);
        }

        Assert::assertCount(10, $sut->visitedPages);
        Assert::assertSame('page-10', $sut->previousPage());
    }
}
