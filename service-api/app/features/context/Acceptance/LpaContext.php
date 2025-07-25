<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use App\Enum\InstructionsAndPreferencesImagesResult;
use Aws\Result;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use BehatTest\LpaTestUtilities;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use stdClass;

/**
 * @property mixed lpa
 * @property string oneTimeCode
 * @property string lpaUid
 * @property string userDob
 * @property string actorId
 * @property string userId
 * @property string userLpaActorToken
 * @property string organisation
 * @property string accessCode
 * @property string userPostCode
 * @property string userSurname
 * @property string userFirstnames
 */
class LpaContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    public string $lpaUid;
    public string $userPostCode;
    public string $userFirstnames;
    public string $userSurname;
    public string $userDob;
    public string $userLpaActorToken;
    public string $oneTimeCode;
    public string $organisation;
    public string $actorId;
    public string $userId;
    public stdClass $lpa;
    public string $accessCode;

    #[Given('I have previously requested the addition of a paper LPA to my account')]
    public function iHavePreviouslyRequestedTheAdditionOfAPaperLPAToMyAccount(): void
    {
        // Not necessary for this context
    }

    #[Given('/^A record of my activation key request is not saved$/')]
    public function aRecordOfMyActivationKeyRequestIsNotSaved(): void
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertNotEquals($lastCommand->getName(), 'PutItem');
    }

    #[Then('/^A record of my activation key request is saved$/')]
    public function aRecordOfMyActivationKeyRequestIsSaved(): void
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertEquals($lastCommand->getName(), 'PutItem');
        Assert::assertEquals($lastCommand->toArray()['TableName'], 'user-actor-lpa-map');
        Assert::assertEquals($lastCommand->toArray()['Item']['SiriusUid'], ['S' => $this->lpaUid]);
        Assert::assertArrayHasKey('ActivateBy', $lastCommand->toArray()['Item']);
    }

    #[Then('/^a record of my activation key request is updated/')]
    public function aRecordOfMyActivationKeyRequestIsUpdated(): void
    {
        $dt = (new DateTime('now'))->add(new DateInterval('P1Y'));

        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertEquals($lastCommand->getName(), 'UpdateItem');
        Assert::assertEquals($lastCommand->toArray()['TableName'], 'user-actor-lpa-map');
        Assert::assertEquals($lastCommand->toArray()['Key']['Id'], ['S' => '111222333444']);
        Assert::assertEquals(
            intval($lastCommand->toArray()['ExpressionAttributeValues'][':a']['N']),
            $dt->getTimestamp(),
            ''
        );
    }

    #[Then('/^A record of the LPA requested is saved to the database$/')]
    public function aRecordOfTheLPARequestedIsSavedToTheDatabase(): void
    {
        //Not used in this context
    }

    #[Given('/^I have been given access to use an LPA via a paper document$/')]
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument(): void
    {
        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->userPostCode          = 'string';
        $this->userFirstnames        = 'Ian Deputy';
        $this->userSurname           = 'Deputy';
        $this->lpa->registrationDate = '2019-09-01';
        $this->userDob               = '1975-10-05';
    }

    #[Given('/^A malformed confirm request is sent which is missing actor code$/')]
    public function aMalformedConfirmRequestIsSentWhichIsMissingActorCode(): void
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => null,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[Given('/^A malformed confirm request is sent which is missing date of birth$/')]
    public function aMalformedConfirmRequestIsSentWhichIsMissingDateOfBirth(): void
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => null,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[Given('/^A malformed confirm request is sent which is missing user id$/')]
    public function aMalformedConfirmRequestIsSentWhichIsMissingUserId(): void
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => null,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[Then('/^I am given a unique access code$/')]
    public function iAmGivenAUniqueAccessCode(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        $codeExpiry = (new DateTime($response['expires']))->format('Y-m-d');
        $in30Days   = (new DateTime(
            '23:59:59 +30 days',
            new DateTimeZone('Europe/London')
        ))->format('Y-m-d');

        Assert::assertArrayHasKey('code', $response);
        Assert::assertNotNull($response['code']);
        Assert::assertEquals($codeExpiry, $in30Days);
        Assert::assertEquals($response['organisation'], $this->organisation);
    }

    #[Given('/^I am on the add an LPA page$/')]
    public function iAmOnTheAddAnLPAPage(): void
    {
        // Not used in this context
    }

    #[Given('/^I am on the create viewer code page$/')]
    public function iAmOnTheCreateViewerCodePage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the dashboard page$/')]
    #[Given('/^I am on the user dashboard page$/')]
    #[Then('/^I cannot see the added LPA$/')]
    #[Then('/^I am taken to the remove an LPA confirmation page for (.*) lpa$/')]
    public function iAmOnTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am taken back to the dashboard page$/')]
    #[Then('/^I cannot see my access codes and their details$/')]
    public function iAmTakenBackToTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[When('/^I attempt to add the same LPA again$/')]
    public function iAttemptToAddTheSameLPAAgain(): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->base->userAccountId,
            ]
        );

        $expectedResponse = [
            'donor'         => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'caseSubtype'   => $this->lpa->caseSubtype,
            'lpaActorToken' => $this->userLpaActorToken,
        ];

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA already added');
        Assert::assertEquals($expectedResponse, $this->getResponseAsJson()['data']);
    }

    #[When('/^I make an additional request for the same LPA$/')]
    public function iMakeAnAdditionalRequestForTheSameLPA(): void
    {
        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        //UserLpaActorMap::getByUserId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'  => $this->lpaUid,
                                'Added'      => (new DateTimeImmutable('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'         => $this->userLpaActorToken,
                                'UserId'     => $this->userId,
                                'ActivateBy' => (new DateTimeImmutable('now'))->format('U'),
                                'DueBy'      => (new DateTimeImmutable('+2 weeks'))->format('Y-m-d\TH:i:s.u\Z'),
                            ]
                        ),
                    ],
                ]
            )
        );

        //UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'  => $this->lpaUid,
                            'Added'      => (new DateTimeImmutable('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'         => $this->userLpaActorToken,
                            'UserId'     => $this->userId,
                            'ActivateBy' => (new DateTimeImmutable('now'))->format('U'),
                            'DueBy'      => (new DateTimeImmutable('+2 weeks'))->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // AWS Request letter response in Given steps
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/cleanse',
            [
                'reference_number' => $this->lpaUid,
                'user-token'       => $this->userId,
                'notes'            => 'Notes',
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    #[When('/^I provide the details from a valid paper LPA which I have already added to my account$/')]
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyAddedToMyAccount(): void
    {
        $differentLpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        // UserLpaActorMap::getAllForUser / getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $differentLpa->uId,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => 'abcd-12345-efgh',
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'donor'                => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'caseSubtype'          => $this->lpa->caseSubtype,
            'lpaActorToken'        => (int)$this->userLpaActorToken,
            'activationKeyDueDate' => null,
        ];

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA already added');

        Assert::assertEquals($expectedResponse, $this->getResponseAsJson()['data']);
    }

    #[When('/^I request to add an LPA that I have requested an activation key for$/')]
    public function iRequestToAddAnLPAThatIHaveRequestedAnActivationKeyFor(): void
    {
        //UserLpaActorMap: getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'  => $this->lpaUid,
                                'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'         => $this->userLpaActorToken,
                                'ActorId'    => $this->actorId,
                                'UserId'     => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                            ]
                        ),
                    ],
                ]
            )
        );

        //LpaService: getByUserLpaActorToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'  => $this->lpaUid,
                            'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'         => $this->userLpaActorToken,
                            'ActorId'    => $this->actorId,
                            'UserId'     => $this->userId,
                            'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                        ]
                    ),
                ]
            )
        );

        // lpaService: getByUserLpaActorToken
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // codes api service call
        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId]))
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertArrayHasKey('actor', $response);
        Assert::assertArrayHasKey('lpa', $response);
        Assert::assertEquals($this->lpaUid, $response['lpa']['uId']);
    }

    #[Given('/^My LPA was registered \\\'([^\\\']*)\\\' 1st September 2019 and LPA is \\\'([^\\\']*)\\\' as clean$/')]
    public function myLPAWasRegistered1stSeptember2019AndLPAIsAsClean($regDate, $cleanseStatus): void
    {
        $this->lpa->lpaIsCleansed = $cleanseStatus !== 'not marked';

        $this->lpa->registrationDate = $regDate === 'before' ? '2018-08-31' : '2019-09-01';
    }

    #[Given('/^The activateBy TTL is removed from the record in the DB$/')]
    public function theActivateByTTLIsRemovedFromTheRecordInTheDB(): void
    {
        // codes api service call
        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId]))
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        //UserLpaActorMapRepository: getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'  => $this->lpaUid,
                                'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'         => $this->userLpaActorToken,
                                'ActorId'    => $this->actorId,
                                'UserId'     => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                            ]
                        ),
                    ],
                ]
            )
        );

        // UserLpaActorMap:: removeActivateBy
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'  => $this->lpaUid,
                            'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'         => $this->userLpaActorToken,
                            'ActorId'    => $this->actorId,
                            'UserId'     => $this->userId,
                            'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                        ]
                    ),
                ]
            )
        );

        // codes api service call
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->apiPost(
            '/v1/add-lpa/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CREATED);

        $response = $this->getResponseAsJson();
        Assert::assertEquals($this->userLpaActorToken, $response['user-lpa-actor-token']);
    }

    #[When('/^I provide the details from a valid paper LPA which I have already requested an activation key for$/')]
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor(): void
    {
        $createdDate = (new DateTime())->modify('-14 days');

        $activationKeyDueDate = DateTimeImmutable::createFromMutable($createdDate);
        $activationKeyDueDate = $activationKeyDueDate
            ->add(new DateInterval('P10D'))
            ->format('Y-m-d');

        // UserLpaActorMap::getAllForUser / getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'  => $this->lpaUid,
                                'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'         => $this->userLpaActorToken,
                                'ActorId'    => $this->actorId,
                                'UserId'     => $this->userId,
                                'ActivateBy' => 123456789,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaService::getByUserLpaActorToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'  => $this->lpaUid,
                            'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'         => $this->userLpaActorToken,
                            'ActorId'    => $this->actorId,
                            'UserId'     => $this->userId,
                            'ActivateBy' => 123456789,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get as part of LpaService::getByUserLpaActorToken
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(['Created' => $createdDate->format('Y-m-d')])
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'donor'                => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'caseSubtype'          => $this->lpa->caseSubtype,
            'activationKeyDueDate' => $activationKeyDueDate,
        ];

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('Activation key already requested for LPA');
        Assert::assertEquals($expectedResponse, $this->getResponseAsJson()['data']);
    }

    #[Then('/^I should be told that I have already added this LPA$/')]
    #[Then('/^I am told an activation key is being sent$/')]
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA(): void
    {
        // Not needed for this context
    }

    #[When('/^I request to add an LPA which has a status other than registered$/')]
    public function iRequestToAddAnLPAWhichHasAStatusOtherThanRegistered(): void
    {
        $this->lpa->status = 'Cancelled';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId]))
        );

        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA status is not registered');
    }

    #[Then('/^I can see all of my access codes and their details$/')]
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails(): void
    {
        // Not needed for this context
    }

    #[Then('/^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/')]
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry): void
    {
        $code1 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => (new DateTime())->modify($code1Expiry)->format('Y-m-d'),
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => $this->accessCode,
        ];

        $code2 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => (new DateTime())->modify($code2Expiry)->format('Y-m-d'),
            'UserLpaActor' => '123456789',
            'Organisation' => 'HSBC',
            'ViewerCode'   => 'XYZABC12345',
        ];

        // LpaService:getLpas

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey($this->userLpaActorToken, $response);
        Assert::assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData($code1),
                        $this->marshalAwsResultData($code2),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // This response is duplicated for the 2nd code
        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => '123456789',
                            'ActorId'   => 23,
                            'UserId'    => '10000000001',
                        ]
                    ),
                ]
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertCount(2, $response);

        Assert::assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($response[0]['Organisation'], $this->organisation);
        Assert::assertEquals($response[0]['ViewerCode'], $this->accessCode);
        Assert::assertEquals($response[0]['ActorId'], $this->actorId);
        Assert::assertEquals($response[0]['Expires'], (new DateTime())->modify($code1Expiry)->format('Y-m-d'));

        Assert::assertEquals($response[1]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[1]['UserLpaActor'], '123456789');
        Assert::assertEquals($response[1]['Organisation'], 'HSBC');
        Assert::assertEquals($response[1]['ViewerCode'], 'XYZABC12345');
        Assert::assertEquals($response[1]['ActorId'], 23);
        Assert::assertEquals($response[1]['Expires'], (new DateTime())->modify($code2Expiry)->format('Y-m-d'));
    }

    #[Then('/^I can see that no organisations have access to my LPA$/')]
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA(): void
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey($this->userLpaActorToken, $response);
        Assert::assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(new Result());

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response);
    }

    #[When('/^I cancel the organisation access code/')]
    public function iCancelTheOrganisationAccessCode(): void
    {
        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to get lpa
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $response);
        Assert::assertArrayHasKey('actor', $response);
        Assert::assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => (new DateTime('tomorrow'))->format('Y-m-d\TH:i:s.u\Z'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // API call to get access codes
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('ViewerCode', $response[0]);
        Assert::assertArrayHasKey('Expires', $response[0]);
        Assert::assertEquals($response[0]['Organisation'], $this->organisation);
        Assert::assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($response[0]['Added'], '2021-01-05 12:34:56');
    }

    #[When('/^I click to check my access code now expired/')]
    public function iClickToCheckMyAccessCodeNowExpired(): void
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to get lpa
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $response);
        Assert::assertArrayHasKey('actor', $response);
        Assert::assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2019-01-05 12:34:56',
                                'Expires'      => '2019-12-05',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // API call to get access codes
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('ViewerCode', $response[0]);
        Assert::assertArrayHasKey('Expires', $response[0]);
        Assert::assertEquals($response[0]['Organisation'], $this->organisation);
        Assert::assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($response[0]['Added'], '2019-01-05 12:34:56');
        Assert::assertNotEquals($response[0]['Expires'], (new DateTime('now'))->format('Y-m-d'));
        //check if the code expiry date is in the past
        Assert::assertGreaterThan(
            strtotime((string) $response[0]['Expires']),
            strtotime((new DateTime('now'))->format('Y-m-d'))
        );
    }

    #[When('/^I check my access codes$/')]
    public function iClickToCheckMyAccessCodes(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to get lpa
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('date', $response);
        Assert::assertArrayHasKey('actor', $response);
        Assert::assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => (new DateTime('tomorrow'))->format('Y-m-d\TH:i:s.u\Z'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // API call to get access codes
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('ViewerCode', $response[0]);
        Assert::assertArrayHasKey('Expires', $response[0]);
        Assert::assertEquals($response[0]['Organisation'], $this->organisation);
        Assert::assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($response[0]['Added'], '2021-01-05 12:34:56');

        //check if the code expiry date is in the past
        Assert::assertGreaterThan(
            strtotime((new DateTime('now'))->format('Y-m-d')),
            strtotime((string) $response[0]['Expires'])
        );
    }

    #[When('/^I confirm cancellation of the chosen viewer code/')]
    public function iConfirmCancellationOfTheChosenViewerCode(): void
    {
        $shareCode = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2021-01-05 12:34:56',
            'Expires'      => '2022-01-05 12:34:56',
            'Cancelled'    => '2022-01-05 12:34:56',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => $this->accessCode,
        ];

        //viewerCodesRepository::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                0 => [
                                    'SiriusUid'    => $this->lpaUid,
                                    'Added'        => '2021-01-05 12:34:56',
                                    'Expires'      => '2022-01-05 12:34:56',
                                    'Cancelled'    => '2022-01-05 12:34:56',
                                    'UserLpaActor' => $this->userLpaActorToken,
                                    'Organisation' => $this->organisation,
                                    'ViewerCode'   => $this->accessCode,
                                ],
                            ]
                        ),
                    ],
                ]
            )
        );

        // ViewerCodes::cancel
        $this->awsFixtures->append(new Result());

        // ViewerCodeService::cancelShareCode
        $this->apiPut(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            ['code' => $shareCode],
            [
                'user-token' => $this->base->userAccountId,
            ]
        );
    }

    #[When('/^I fill in the form and click the cancel button$/')]
    public function iFillInTheFormAndClickTheCancelButton(): void
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([]));

        // API call for finding all the users added LPAs
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode([])
            )
        );

        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[Given('/^I have 2 codes for one of my LPAs$/')]
    public function iHave2CodesForOneOfMyLPAs(): void
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iHaveCreatedAnAccessCode();
    }

    #[Given('/^I have been given access to use an LPA via credentials$/')]
    #[Given('/^I have added an LPA to my account$/')]
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'));

        $this->oneTimeCode       = 'XYUPHWQRECHV';
        $this->lpaUid            = '700000000054';
        $this->userDob           = '1975-10-05';
        $this->actorId           = '700000000054';
        $this->userId            = '111222333444';
        $this->userLpaActorToken = '111222333444';
    }

    #[Given('/^I have created an access code$/')]
    #[Given('/^I have generated an access code for an organisation and can see the details$/')]
    public function iHaveCreatedAnAccessCode(): void
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
        $this->iAmGivenAUniqueAccessCode();
    }

    #[When('/^I request to add an LPA that does not exist$/')]
    public function iRequestToAddAnLPAThatDoesNotExist(): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(['actor' => ''])
            )
        );

        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NOT_FOUND
            )
        );

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->ui->assertSession()->responseContains('Code validation failed');
    }

    #[When('/^I request to add an LPA with a missing actor code$/')]
    public function iRequestToAddAnLPAWithAMissingActorCode(): void
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => null,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[When('/^I request to add an LPA with a missing date of birth$/')]
    public function iRequestToAddAnLPAWithAMissingDateOfBirth(): void
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => null,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    #[When('/^I request to add an LPA with a missing user id$/')]
    public function iRequestToAddAnLPAWithAMissingUserId(): void
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => null,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    #[When('/^I request to add an LPA with valid details$/')]
    #[When('/^I confirmed to add an LPA to my account$/')]
    public function iRequestToAddAnLPAWithValidDetails(): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId]))
        );

        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertArrayHasKey('actor', $response);
        Assert::assertArrayHasKey('lpa', $response);
        Assert::assertEquals($this->lpaUid, $response['lpa']['uId']);
    }

    #[When('/^I request to give an organisation access to one of my LPAs$/')]
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';
        $actorId            = 700000000054;

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => (string)$actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::add
        $this->awsFixtures->append(new Result());

        // ViewerCodeService::createShareCode
        $this->apiPost(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            ['organisation' => $this->organisation],
            [
                'user-token' => $this->userId,
            ]
        );
    }

    #[When('/^I request to give an organisation access to one of my new LPA$/')]
    public function iRequestToGiveAnOrganisationAccessToOneOfMyNewLPA(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';
        $actorId            = 700000000054;
        $lpaUid             = 'M-XXXX-1111-YYYY';

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'LpaUid'    => $lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => (string)$actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::add
        $this->awsFixtures->append(new Result());

        // ViewerCodeService::createShareCode
        $this->apiPost(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            ['organisation' => $this->organisation],
            [
                'user-token' => $this->userId,
            ]
        );
    }

    #[Given('/^I request to go back and try again$/')]
    public function iRequestToGoBackAndTryAgain(): void
    {
        // Not needed for this context
    }

    #[When('/^I request to view an LPA which has instructions and preferences$/')]
    public function iRequestToViewAnLPAWhichHasInstructionsAndPreferences(): void
    {
        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->base->userAccountId,
                        ]
                    ),
                ]
            )
        );

        $imageResponse             = new stdClass();
        $imageResponse->uId        = (int) $this->lpaUid;
        $imageResponse->status     = 'COLLECTION_COMPLETE';
        $imageResponse->signedUrls = [
            'iap-' . $this->lpaUid . '-instructions' => 'https://image_url',
            'iap-' . $this->lpaUid . '-preferences'  => 'https://image_url',
        ];

        // InstructionsAndPreferencesImages::getInstructionsAndPreferencesImages
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($imageResponse)));

        // API call to request an activation key
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/images',
            [
                'user-token' => $this->base->userAccountId,
            ]
        );
    }

    #[When('/^I request to view an LPA which status is "([^"]*)"$/')]
    #[When('/^I request to remove an LPA from my account that is (.*)$/')]
    public function iRequestToViewAnLPAWhichStatusIs($status): void
    {
        $this->lpa->status = $status;

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->base->userAccountId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->base->userAccountId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        if ($status === 'Revoked') {
            Assert::assertEmpty($response);
        } else {
            Assert::assertEquals($this->userLpaActorToken, $response['user-lpa-actor-token']);
            Assert::assertEquals($this->lpaUid, $response['lpa']['uId']);
            Assert::assertEquals($status, $response['lpa']['status']);
        }
    }

    #[Then('/^I should be able to click a link to go and create the access codes$/')]
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes(): void
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
    }

    #[Then('/^I should be shown the details of the cancelled viewer code with cancelled status/')]
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertArrayHasKey('Cancelled', $response);
    }

    #[Then('/^I should be shown the details of the viewer code with status(.*)/')]
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus(): void
    {
        // Not needed for this context
    }

    #[Then('/^I should be taken back to the access code summary page/')]
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage(): void
    {
        // Not needed for this context
    }

    #[Then('/^I should be told that I have not created any access codes yet$/')]
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet(): void
    {
        // Not needed for this context
    }

    #[When('/^I view my dashboard$/')]
    public function iViewMyDashboard(): void
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->base->userAccountId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->setLastRequest($this->apiFixtures->getLastRequest());
    }

    #[Then('/^I want to be asked for confirmation prior to cancellation/')]
    public function iWantToBeAskedForConfirmationPriorToCancellation(): void
    {
        // Not needed for this context
    }

    #[When('/^I want to cancel the access code for an organisation$/')]
    public function iWantToCancelTheAccessCodeForAnOrganisation(): void
    {
        // Not needed for this context
    }

    #[Then('/^I want to see the option to cancel the code$/')]
    public function iWantToSeeTheOptionToCancelTheCode(): void
    {
        // Not needed for this context
    }

    #[When('/^One of the generated access code has expired$/')]
    public function oneOfTheGeneratedAccessCodeHasExpired(): void
    {
        // Not needed for this context
    }

    #[Then('/^The correct LPA is found and I can confirm to add it$/')]
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt(): void
    {
        // not needed for this context
    }

    #[Given('/^The details I provided resulted in a partial match$/')]
    public function theDetailsIProvidedResultedInAPartialMatch(): void
    {
        // not needed for this context
    }

    #[Then('/^The full LPA is displayed with the correct (.*)$/')]
    public function theFullLPAIsDisplayedWithTheCorrect($message): void
    {
        // Not needed for this context
    }

    #[Given('/^The LPA has not been added$/')]
    public function theLPAHasNotBeenAdded(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response);
    }

    #[Then('/^The LPA is not found$/')]
    public function theLPAIsNotFound(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response['data']);
    }

    #[Then('/^The LPA is not found and I am told it was a bad request$/')]
    public function theLPAIsNotFoundAndIAmToldItWasABadRequest(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response['data']);
    }

    #[Given('/^The LPA is successfully added$/')]
    public function theLPAIsSuccessfullyAdded(): void
    {
        $this->userLpaActorToken = '13579';
        $now                     = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');

        // codes api service call
        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId]))
        );

        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // called twice
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([]));
        // UserLpaActorMap::create
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => $now,
                        ]
                    ),
                ]
            )
        );

        // codes api service call
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->apiPost(
            '/v1/add-lpa/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CREATED);

        $response = $this->getResponseAsJson();
        Assert::assertNotNull($response['user-lpa-actor-token']);
    }

    #[Then('/^The LPA should not be found$/')]
    public function theLPAShouldNotBeFound(): void
    {
        // Not needed for this context
    }

    #[When('/^I click to check the access codes$/')]
    public function iClickToCheckTheAccessCodes(): void
    {
        $code1 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => '2020-12-01T00:00:00Z',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => $this->accessCode,
            'Viewed'       => false,
        ];

        $code2 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => '2020-12-01T00:00:00Z',
            'UserLpaActor' => '65d6833a-66d3-430f-8cf6-9e4fb1d851f1',
            'Organisation' => 'SomeOrganisation',
            'ViewerCode'   => 'B97LRK3U68PE',
            'Viewed'       => false,
        ];

        // LpaService:getLpas

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey($this->userLpaActorToken, $response);
        Assert::assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData($code1),
                        $this->marshalAwsResultData($code2),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // This response is duplicated for the 2nd code
        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => '65d6833a-66d3-430f-8cf6-9e4fb1d851f1',
                            'ActorId'   => '4455',
                            'UserId'    => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
                        ]
                    ),
                ]
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
        $response = $this->getResponseAsJson();

        Assert::assertCount(2, $response);

        Assert::assertEquals($response[0]['Organisation'], $this->organisation);
        Assert::assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($response[0]['ViewerCode'], $this->accessCode);
        Assert::assertEquals($response[0]['ActorId'], $this->actorId);

        Assert::assertEquals($response[1]['Organisation'], 'SomeOrganisation');
        Assert::assertEquals($response[1]['SiriusUid'], '700000000054');
        Assert::assertEquals($response[1]['UserLpaActor'], '65d6833a-66d3-430f-8cf6-9e4fb1d851f1');
        Assert::assertEquals($response[1]['ViewerCode'], 'B97LRK3U68PE');
        Assert::assertEquals($response[1]['ActorId'], '4455');
    }

    #[Given('/^Co\-actors have also created access codes for the same LPA$/')]
    public function coActorsHaveAlsoCreatedAccessCodesForTheSameLPA(): void
    {
        // Not needed for this context
    }

    #[Then('/^I can see all of the access codes and their details$/')]
    public function iCanSeeAllOfTheAccessCodesAndTheirDetails(): void
    {
        // Not needed for this context
    }

    #[Then('/^I can see the name of the organisation that viewed the LPA$/')]
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA(): void
    {
        // Not needed for this context
    }

    #[Given('/^I have shared the access code with organisations to view my LPA$/')]
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA(): void
    {
        // Not needed for this context
    }

    #[When('/^I click to check my access codes that is used to view LPA$/')]
    public function iClickToCheckMyAccessCodesThatIsUsedToViewLPA(): void
    {
        $code1 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => '2020-12-01T00:00:00Z',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => $this->accessCode,
            'Viewed'       => true,
            'ViewedBy'     => 'TestOrg1',
        ];
        $code2 = [
            'SiriusUid'    => $this->lpaUid,
            'Added'        => '2020-01-01T00:00:00Z',
            'Expires'      => '2020-12-01T00:00:00Z',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => 'B97LRK3U68PE',
            'Viewed'       => true,
            'ViewedBy'     => 'TestOrg2',
        ];

        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to get lpa
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData($code1),
                        $this->marshalAwsResultData($code2),
                    ],
                ]
            )
        );

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ],
                    ),
                ]
            )
        );

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // API call to get access codes
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('Viewed', $response[0]);
        Assert::assertEquals($response[0]['ViewerCode'], $this->accessCode);
        Assert::assertEquals($response[0]['ViewedBy'], 'TestOrg1');
        Assert::assertEquals($response[1]['ViewerCode'], 'B97LRK3U68PE');
        Assert::assertEquals($response[1]['ViewedBy'], 'TestOrg2');
    }

    #[Then('/^The LPA is removed$/')]
    #[Then('/^The LPA is removed and I am taken back to dashboard page$/')]
    public function theLPAIsRemoved(): void
    {
        $actorId = 700000000054;
        $expected_response = [
                'donor' => [
                    'uId' => '700000000053',
                    'firstname' => 'Ian',
                    'surname' => 'Deputy',
                ],
                'caseSubtype' => 'hw'
        ];

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData( // 1st code is active
                            [
                                'Id'           => '1',
                                'ViewerCode'   => '123ABCD6789',
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                                'Expires'      => (new DateTime())->modify('+1 month')->format('Y-m-d'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => 'Some Organisation 1',
                            ]
                        ),
                        $this->marshalAwsResultData( // 2nd code has expired
                            [
                                'Id'           => '2',
                                'ViewerCode'   => 'YG41BCD693FH',
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                                'Expires'      => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => 'Some Organisation 2',
                            ]
                        ),
                        $this->marshalAwsResultData( // 3rd code has already been cancelled
                            [
                                'Id'           => '3',
                                'ViewerCode'   => 'RL2AD1936KV2',
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                                'Expires'      => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                                'Cancelled'    => (new DateTime())->modify('-2 months')->format('Y-m-d'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => 'Some Organisation 3',
                            ]
                        ),
                    ],
                ]
            )
        );
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result());

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result()); // 2nd code has expired therefore isn't cancelled

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result()); // 3rd code has already been cancelled

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // UserLpaActorMap::delete
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime())->modify('-6 months')->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // API call to remove an LPA
        $this->apiDelete(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ],
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertArrayHasKey('lpa', $response);
        Assert::assertArrayHasKey('donor', $response['lpa']);
        Assert::assertArrayHasKey('caseSubtype', $response['lpa']);
        Assert::assertEquals($response['lpa'], $expected_response);
    }

    #[Given('/^I am on the add an older LPA page$/')]
    #[Given('/^I am on the Check we\'ve found the right LPA page$/')]
    public function iAmOnTheAddAnOlderLPAPage(): void
    {
        // Not needed for this context
    }

    #[Then('/^a repeat request for a letter containing a one time use code is made$/')]
    public function aRepeatRequestForALetterContainingAOneTimeUseCodeIsMade(): void
    {
        //UserLpaActorMap: getByUserId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id'         => $this->userLpaActorToken,
                                'UserId'     => $this->base->userAccountId,
                                'SiriusUid'  => $this->lpaUid,
                                'ActorId'    => $this->actorId,
                                'Added'      => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                                'ActivateBy' => (new DateTime())->add(new DateInterval('P1Y'))->getTimestamp(),
                            ]
                        ),
                    ],
                ]
            )
        );

        //UserLpaActorMap: get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'         => $this->userLpaActorToken,
                            'UserId'     => $this->base->userAccountId,
                            'SiriusUid'  => $this->lpaUid,
                            'ActorId'    => $this->actorId,
                            'Added'      => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                            'ActivateBy' => (new DateTime())->add(new DateInterval('P1Y'))->getTimestamp(),
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // Done twice due to our codes interdependencies
        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // CheckLpaCleansed: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // request a code to be generated and letter to be sent
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NO_CONTENT,
                []
            )
        );

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/older-lpa/confirm',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => true,
            ],
            [
                'user-token' => $this->base->userAccountId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    #[Then('/^a letter is requested containing a one time use code$/')]
    public function aLetterIsRequestedContainingAOneTimeUseCode(): void
    {
        // In some way which I am not able to understand *at all* when this step is hit on Circle CI
        // the apiFixtures contain a left-over item from a previous step. This does not happen when
        // run locally. So the quick and dirty fix is to just ensure the queue is always cleared.
        if ($this->apiFixtures->count() > 0) {
            echo "WARNING apiFixtures should be empty and isn't";
            $this->apiFixtures->reset();
        }

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(
                    [
                        'Created' => null,
                    ]
                )
            )
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // request a code to be generated and letter to be sent
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NO_CONTENT,
                []
            )
        );

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/older-lpa/confirm',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    #[Given('/^I confirm the details I provided are correct$/')]
    #[When('/^I confirm details shown to me of the found LPA are correct$/')]
    public function iConfirmTheDetailsIProvidedAreCorrect(): void
    {
        // Not needed for this context
    }

    #[When('/^I provide the details from a valid paper LPA document$/')]
    public function iProvideTheDetailsFromAValidPaperLPADocument(): void
    {
        // Not needed for this context
    }

    #[When('/^I provide details of an LPA that does not exist$/')]
    public function iProvideDetailsOfAnLPAThatDoesNotExist(): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NOT_FOUND,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => 700000004321,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    #[When('/^I provide details "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" that do not match the paper document$/')]
    public function iProvideDetailsThatDoNotMatchThePaperDocument($firstnames, $lastname, $postcode, $dob): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $firstnames,
                'last_name'            => $lastname,
                'dob'                  => $dob,
                'postcode'             => $postcode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    #[When('/^I provide details "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" that match a valid paper document$/')]
    public function iProvideDetailsThatMatchThePaperDocument($firstnames, $lastname, $postcode, $dob): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $this->lpa->lpaIsCleansed    = true;
        $this->lpa->donor->firstname = 'Rachel';
        $this->lpa->donor->surname   = 'S’anderson';

        $this->lpaUid         = '700000000047';
        $this->userFirstnames = $firstnames;
        $this->userSurname    = $lastname;
        $this->userDob        = $dob;
        $this->userPostCode   = $postcode;
        $this->actorId        = '700000000799';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(new Result([]));

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Created' => null])));

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'actor'       => json_decode(json_encode($this->lpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $this->lpaUid,
            'caseSubtype' => $this->lpa->caseSubtype,
            'donor'       => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
        ];

        Assert::assertArrayNotHasKey('attorney', $this->getResponseAsJson());
        Assert::assertEquals($expectedResponse, $this->getResponseAsJson());
    }

    #[Then('/^I am informed that an LPA could not be found with these details$/')]
    #[Then('/^I am asked for my role on the LPA$/')]
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails(): void
    {
        // Not needed for this context
    }

    #[When('/^I provide details from an LPA registered before Sept 2019$/')]
    public function iProvideDetailsFromAnLPARegisteredBeforeSept2019(): void
    {
        $this->lpa->registrationDate = '2019-08-31';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    #[Then('/^I am told that I cannot request an activation key$/')]
    public function iAmToldThatICannotRequestAnActivationKey(): void
    {
        // Not needed for this context
    }

    #[When('/^I provide the details from a valid paper document that already has an activation key$/')]
    public function iProvideTheDetailsFromAValidPaperDocumentThatAlreadyHasAnActivationKey(): void
    {
        $createdDate = (new DateTime())->modify('-14 days');

        $activationKeyDueDate = DateTimeImmutable::createFromMutable($createdDate);
        $activationKeyDueDate = $activationKeyDueDate
            ->add(new DateInterval('P10D'))
            ->format('Y-m-d');

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(['Created' => $createdDate->format('Y-m-d')])
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'donor'                => [
                'id'          => $this->lpa->donor->id,
                'uId'         => $this->lpa->donor->uId,
                'email'       => $this->lpa->donor->email,
                'dob'         => $this->lpa->donor->dob,
                'salutation'  => $this->lpa->donor->salutation,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
                'companyName' => $this->lpa->donor->companyName,
                'addresses'   => json_decode(json_encode($this->lpa->donor->addresses), true),
            ],
            'caseSubtype'          => $this->lpa->caseSubtype,
            'activationKeyDueDate' => $activationKeyDueDate,
        ];
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA has an activation key already');

        Assert::assertEquals($expectedResponse, $this->getResponseAsJson()['data']);
    }

    #[Then('/^I am told that I have an activation key for this LPA and where to find it$/')]
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt(): void
    {
        // Not needed for this context
    }

    #[Given('/^A malformed request is sent which is missing a data attribute$/')]
    public function aMalformedRequestIsSentWhichIsMissingADataAttribute(): void
    {
        $dataAttributes = [
            'reference_number'     => (int) $this->lpaUid,
            'first_names'          => $this->userFirstnames,
            'last_name'            => $this->userSurname,
            'dob'                  => $this->userDob,
            'postcode'             => $this->userPostCode,
            'force_activation_key' => false,
        ];

        foreach (array_keys($dataAttributes) as $name) {
            $dataAttributes[$name] = null;

            // API call to request an activation key
            $this->apiPost(
                '/v1/older-lpa/validate',
                $dataAttributes,
                [
                    'user-token' => $this->userId,
                ]
            );

            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    #[Then('/^I am told that something went wrong$/')]
    public function iAmToldThatSomethingWentWrong(): void
    {
        // Not needed for this context
    }

    #[Given('/^The status of the LPA changed from Registered to Suspended$/')]
    public function theStatusOfTheLPAChangedFromRegisteredToSuspended(): void
    {
        $this->lpa->status = 'Suspended';

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response);
    }

    #[When('/^I check my access codes of the status changed LPA$/')]
    #[When('/^I request to give an organisation access to the LPA whose status changed to Revoked$/')]
    public function iCheckMyAccessCodesOfTheStatusChangedLpa(): void
    {
        $this->lpa->status = 'Revoked';

        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to get lpa
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response);
    }

    #[When('/^The status of the LPA got Revoked$/')]
    public function theStatusOfTheLpaGotRevoked(): void
    {
        // Not needed for this context
    }

    #[Given('/^I already have a valid activation key for my LPA$/')]
    #[Given('/^I provide the additional details asked$/')]
    #[Given('/^I am asked to consent and confirm my details$/')]
    public function iAlreadyHaveAValidActivationKeyForMyLPA(): void
    {
        // Not needed for this context
    }

    #[Given('/^I lost the letter containing my activation key$/')]
    public function iLostTheLetterContainingMyActivationKey(): void
    {
        // Not needed for this context
    }

    #[When('/^I request for a new activation key again$/')]
    #[When('/^I repeat my request for an activation key$/')]
    public function iRequestForANewActivationKeyAgain(): void
    {
        $this->lpa->lpaIsCleansed = true;
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // request a code to be generated and letter to be sent
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NO_CONTENT,
                []
            )
        );

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/older-lpa/confirm',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => true,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    #[Then('/^I am told a new activation key is posted to the provided postcode$/')]
    #[Then('/^I am asked for my contact details$/')]
    #[Then('/^I should expect it within 4 time$/')]
    public function iAmToldANewActivationKeyIsPostedToTheProvidedPostcode(): void
    {
        // Not needed for this context
    }

    #[When('/^I provide the attorney details from a valid paper LPA document$/')]
    public function iProvideTheAttorneyDetailsFromAValidPaperLPADocument(): void
    {
        $this->lpa          = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));
        $sanitizedSiriusLpa = LpaTestUtilities::SanitiseSiriusLpaUIds($this->lpa);

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(new Result([]));

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Created' => null])));

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $sanitizedSiriusLpa->uId,
                'first_names'          => $this->lpa->attorneys[0]->firstname,
                'last_name'            => $this->lpa->attorneys[0]->surname,
                'dob'                  => $this->lpa->attorneys[0]->dob,
                'postcode'             => $this->lpa->attorneys[0]->addresses[0]->postcode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'actor'       => json_decode(json_encode($sanitizedSiriusLpa->attorneys[0]), true),
            'role'        => 'attorney',
            'lpa-id'      => $sanitizedSiriusLpa->uId,
            'caseSubtype' => $sanitizedSiriusLpa->caseSubtype,
            'donor'       => [
                'uId'         => $sanitizedSiriusLpa->donor->uId,
                'firstname'   => $sanitizedSiriusLpa->donor->firstname,
                'middlenames' => $sanitizedSiriusLpa->donor->middlenames,
                'surname'     => $sanitizedSiriusLpa->donor->surname,
            ],
            'attorney'    => [
                'uId'         => $sanitizedSiriusLpa->attorneys[0]->uId,
                'firstname'   => $sanitizedSiriusLpa->attorneys[0]->firstname,
                'middlenames' => $sanitizedSiriusLpa->attorneys[0]->middlenames,
                'surname'     => $sanitizedSiriusLpa->attorneys[0]->surname,
            ],
        ];
        Assert::assertEquals($expectedResponse, $this->getResponseAsJson());
    }

    #[Then('/^I am shown the details of an LPA$/')]
    #[Then('/^I being the donor on the LPA I am not shown the attorney details$/')]
    public function iAmShownDetailsOfAnLpa(): void
    {
        $this->lpa                = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));
        $this->lpa->lpaIsCleansed = true;

        $sanitizedSiriusLpa = LpaTestUtilities::SanitiseSiriusLpaUIds($this->lpa);

        $this->lpaUid         = '700000000047';
        $this->userFirstnames = 'Rachel';
        $this->userSurname    = 'Sanderson';
        $this->userDob        = '1948-11-01';
        $this->userPostCode   = 'DN37 5SH';
        $this->actorId        = '700000000799';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(new Result([]));

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Created' => null])));

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'actor'       => json_decode(json_encode($sanitizedSiriusLpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $this->lpaUid,
            'caseSubtype' => $sanitizedSiriusLpa->caseSubtype,
            'donor'       => [
                'uId'         => $sanitizedSiriusLpa->donor->uId,
                'firstname'   => $sanitizedSiriusLpa->donor->firstname,
                'middlenames' => $sanitizedSiriusLpa->donor->middlenames,
                'surname'     => $sanitizedSiriusLpa->donor->surname,
            ],
        ];

        Assert::assertArrayNotHasKey('attorney', $this->getResponseAsJson());
        Assert::assertEquals($expectedResponse, $this->getResponseAsJson());
    }

    #[Then('/^I being the attorney on the LPA I am shown the donor details$/')]
    public function iBeingTheAttorneyOnTheLpaIAmShownTheDonorDetails(): void
    {
        $this->lpa                = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));
        $this->lpa->lpaIsCleansed = true;

        $sanitizedSiriusLpa = LpaTestUtilities::SanitiseSiriusLpaUIds($this->lpa);

        $this->lpaUid         = '700000000047';
        $this->userFirstnames = 'jean';
        $this->userSurname    = 'Sanderson';
        $this->userDob        = '1990-05-04';
        $this->userPostCode   = 'DN37 5SH';
        $this->actorId        = '700000000815';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(new Result([]));

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // check if actor has a code
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Created' => null])));

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $expectedResponse = [
            'actor'       => json_decode(json_encode($sanitizedSiriusLpa->attorneys[0]), true),
            'role'        => 'attorney',
            'lpa-id'      => $this->lpaUid,
            'attorney'    => [
                'uId'         => $sanitizedSiriusLpa->attorneys[0]->uId,
                'firstname'   => $sanitizedSiriusLpa->attorneys[0]->firstname,
                'middlenames' => $sanitizedSiriusLpa->attorneys[0]->middlenames,
                'surname'     => $sanitizedSiriusLpa->attorneys[0]->surname,
            ],
            'caseSubtype' => $sanitizedSiriusLpa->caseSubtype,
            'donor'       => [
                'uId'         => $sanitizedSiriusLpa->donor->uId,
                'firstname'   => $sanitizedSiriusLpa->donor->firstname,
                'middlenames' => $sanitizedSiriusLpa->donor->middlenames,
                'surname'     => $sanitizedSiriusLpa->donor->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $this->getResponseAsJson());
    }

    #[When('I provide details of an LPA that is not registered')]
    public function iProvideDetailsDetailsOfAnLpaThatIsNotRegistered(): void
    {
        $this->lpa->status = 'Pending';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    #[When('/^I confirm details of the found LPA are correct$/')]
    #[Then('/^I am told my activation key is being sent$/')]
    public function iConfirmDetailsOfTheFoundLPAAreCorrect(): void
    {
        $earliestRegDate = '2019-09-01';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // LpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // request a code to be generated and letter to be sent
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_NO_CONTENT,
                []
            )
        );

        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/older-lpa/confirm',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => $this->userPostCode,
                'force_activation_key' => true,
            ],
            [
                'user-token' => $this->userId,
            ]
        );
        if (!$this->lpa->lpaIsCleansed && $this->lpa->registrationDate < $earliestRegDate) {
            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        } else {
            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
        }
    }

    #[When('/^I confirm that the data is correct and click the confirm and submit button$/')]
    public function iConfirmThatTheDataIsCorrectAndClickTheConfirmAndSubmitButton(): void
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // lpaService: getByUid
        $this->apiFixtures->append(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // AWS Request letter response in Given steps
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'        => $this->userLpaActorToken,
                            'UserId'    => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId'   => $this->actorId,
                            'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                        ]
                    ),
                ]
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/cleanse',
            [
                'reference_number' => $this->lpaUid,
                'user-token'       => $this->userId,
                'notes'            => 'Notes',
                'actor_id'         => $this->actorId,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    #[When('/^I am told my activation key request has been received$/')]
    #[Then('/^I should expect it within 2 weeks time$/')]
    #[Then('/^I should expect it within 4 weeks time$/')]
    #[Then('/^I will receive an email confirming this information$/')]
    public function iAmToldMyActivationKeyRequestHasBeenReceived(): void
    {
        //Not needed for this context
    }

    #[When('I confirm the incorrect details of the found LPA and flag is turned :flagStatus')]
    public function iConfirmDetailsOfTheFoundLPAAreCorrectAndFlagIsTurned($flagStatus): void
    {
        $this->lpa->status = 'Registered';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode($this->lpa)
            )
        );

        // API call to request an activation key
        $this->apiPost(
            '/v1/older-lpa/validate',
            [
                'reference_number'     => (int) $this->lpaUid,
                'first_names'          => $this->userFirstnames,
                'last_name'            => $this->userSurname,
                'dob'                  => $this->userDob,
                'postcode'             => 'Wrong',
                'force_activation_key' => false,
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        if ($flagStatus === 'ON') {
            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
        } else {
            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    #[Then('/^my LPA is shown with instructions and preferences images$/')]
    public function myLPAIsShownWithInstructionsAndPreferencesImages(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
        $response = $this->getResponseAsJson();

        Assert::assertEquals((int) $this->lpaUid, $response['uId']);
        Assert::assertEquals(InstructionsAndPreferencesImagesResult::COLLECTION_COMPLETE->value, $response['status']);
        Assert::assertCount(2, $response['signedUrls']);
    }
}
