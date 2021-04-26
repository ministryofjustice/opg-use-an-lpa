<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use DateTime;
use DateTimeZone;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;

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

    /**
     * @Given /^I have already requested an activation key$/
     */
    public function iHaveAlreadyRequestedAnActivationKey()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument()
    {
        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->userPostCode = 'string';
        $this->userFirstnames = 'Ian Deputy';
        $this->userSurname = 'Deputy';
        $this->lpa->registrationDate = '2019-09-01';
        $this->userDob = '1975-10-05';
    }

    /**
     * @Given /^A malformed confirm request is sent which is missing actor code$/
     */
    public function aMalformedConfirmRequestIsSentWhichIsMissingActorCode()
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => null,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    /**
     * @Given /^A malformed confirm request is sent which is missing date of birth$/
     */
    public function aMalformedConfirmRequestIsSentWhichIsMissingDateOfBirth()
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => null,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    /**
     * @Given /^A malformed confirm request is sent which is missing user id$/
     */
    public function aMalformedConfirmRequestIsSentWhichIsMissingUserId()
    {
        $this->userLpaActorToken = '13579';

        $this->apiPost(
            '/v1/actor-codes/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => null,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        $codeExpiry = (new DateTime($response['expires']))->format('Y-m-d');
        $in30Days = (new DateTime(
            '23:59:59 +30 days',
            new DateTimeZone('Europe/London')
        ))->format('Y-m-d');

        assertArrayHasKey('code', $response);
        assertNotNull($response['code']);
        assertEquals($codeExpiry, $in30Days);
        assertEquals($response['organisation'], $this->organisation);
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not used in this context
    }

    /**
     * @Given /^I am on the create viewer code page$/
     */
    public function iAmOnTheCreateViewerCodePage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the dashboard page$/
     * @Given /^I am on the user dashboard page$/
     * @Then /^I cannot see the added LPA$/
     */
    public function iAmOnTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->base->userAccountId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('Lpa already added');
        $this->ui->assertSession()->responseContains('actor');
        $this->ui->assertSession()->responseContains($this->lpaUid);
        $this->ui->assertSession()->responseContains($this->userLpaActorToken);
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to add an LPA which has a status other than registered$/
     */
    public function iRequestToAddAnLPAWhichHasAStatusOtherThanRegistered()
    {
        $this->lpa->status = 'Cancelled';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/validate')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId])));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA status is not registered');
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
    {
        $code1 = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2020-01-01T00:00:00Z',
            'Expires' => (new DateTime())->modify($code1Expiry)->format('Y-m-d'),
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode' => $this->accessCode,
        ];

        $code2 = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2020-01-01T00:00:00Z',
            'Expires' => (new DateTime())->modify($code2Expiry)->format('Y-m-d'),
            'UserLpaActor' => '123456789',
            'Organisation' => 'HSBC',
            'ViewerCode' => 'XYZABC12345',
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
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey($this->userLpaActorToken, $response);
        assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => '123456789',
                            'ActorId' => 23,
                            'UserId' => '10000000001',
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

        assertCount(2, $response);

        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['ViewerCode'], $this->accessCode);
        assertEquals($response[0]['ActorId'], $this->actorId);
        assertEquals($response[0]['Expires'], (new DateTime())->modify($code1Expiry)->format('Y-m-d'));

        assertEquals($response[1]['SiriusUid'], $this->lpaUid);
        assertEquals($response[1]['UserLpaActor'], '123456789');
        assertEquals($response[1]['Organisation'], 'HSBC');
        assertEquals($response[1]['ViewerCode'], 'XYZABC12345');
        assertEquals($response[1]['ActorId'], 23);
        assertEquals($response[1]['Expires'], (new DateTime())->modify($code2Expiry)->format('Y-m-d'));
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey($this->userLpaActorToken, $response);
        assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

        assertEmpty($response);
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'Expires' => '2022-01-05 12:34:56',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2019-01-05 12:34:56',
                                'Expires' => '2019-12-05',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2019-01-05 12:34:56');
        assertNotEquals($response[0]['Expires'], (new DateTime('now'))->format('Y-m-d'));
        //check if the code expiry date is in the past
        assertGreaterThan(strtotime($response[0]['Expires']), strtotime((new DateTime('now'))->format('Y-m-d')));
    }

    /**
     * @When /^I check my access codes$/
     */
    public function iClickToCheckMyAccessCodes()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['uId'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'Expires' => '2022-01-05 12:34:56',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2021-01-05 12:34:56');

        //check if the code expiry date is in the past
        assertGreaterThan(strtotime((new DateTime('now'))->format('Y-m-d')), strtotime($response[0]['Expires']));
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        $shareCode = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2021-01-05 12:34:56',
            'Expires' => '2022-01-05 12:34:56',
            'Cancelled' => '2022-01-05 12:34:56',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode' => $this->accessCode,
        ];

        //viewerCodesRepository::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                0 => [
                                    'SiriusUid' => $this->lpaUid,
                                    'Added' => '2021-01-05 12:34:56',
                                    'Expires' => '2022-01-05 12:34:56',
                                    'Cancelled' => '2022-01-05 12:34:56',
                                    'UserLpaActor' => $this->userLpaActorToken,
                                    'Organisation' => $this->organisation,
                                    'ViewerCode' => $this->accessCode,
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

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     * @When /^I request to return to the dashboard page/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        // Not needed for this context
    }

    /**
     * @When /^I fill in the form and click the cancel button$/
     */
    public function iFillInTheFormAndClickTheCancelButton()
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([]));

        // API call for finding all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
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

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iHaveCreatedAnAccessCode();
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'));

        $this->oneTimeCode = 'XYUPHWQRECHV';
        $this->lpaUid = '700000000054';
        $this->userDob = '1975-10-05';
        $this->actorId = '700000000054';
        $this->userId = '111222333444';
        $this->userLpaActorToken = '111222333444';
    }

    /**
     * @Given /^I have created an access code$/
     * @Given /^I have generated an access code for an organisation and can see the details$/
     */
    public function iHaveCreatedAnAccessCode()
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
        $this->iAmGivenAUniqueAccessCode();
    }

    /**
     * @When /^I request an activation key again within 14 calendar days$/
     */
    public function iRequestAnActivationKeyAgainWithin14CalendarDays()
    {
        $createdDate = (new DateTime())->modify('-1 day')->format('Y-m-d');

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // check if actor has a code
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/exists')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Created' => $createdDate
                        ]
                    )
                )
            );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => $this->lpaUid,
                'first_names'       => $this->userFirstnames,
                'last_name'         => $this->userSurname,
                'dob'               => $this->userDob,
                'postcode'          => $this->userPostCode
            ],
            [
                'user-token' => $this->userId,
            ]
        );
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('activation_key_created');
        $this->ui->assertSession()->responseContains($createdDate);
        $this->ui->assertSession()->responseContains(
            'LPA not eligible as an activation key already exists'
        );
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/validate')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => ''])));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
        $this->ui->assertSession()->responseContains('Code validation failed');
    }

    /**
     * @When /^I request to add an LPA with a missing actor code$/
     */
    public function iRequestToAddAnLPAWithAMissingActorCode()
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => null,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    /**
     * @When /^I request to add an LPA with a missing date of birth$/
     */
    public function iRequestToAddAnLPAWithAMissingDateOfBirth()
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => null,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    /**
     * @When /^I request to add an LPA with a missing user id$/
     */
    public function iRequestToAddAnLPAWithAMissingUserId()
    {
        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => null,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     * @When /^I confirmed to add an LPA to my account$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // codes api service call
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/validate')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId])));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost(
            '/v1/add-lpa/validate',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertArrayHasKey('actor', $response);
        assertArrayHasKey('lpa', $response);
        assertEquals($this->lpaUid, $response['lpa']['uId']);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->lpa->status = $status;

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->base->userAccountId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->base->userAccountId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        if($status == "Revoked"){
            assertEmpty($response);
        }
        else {
            assertEquals($this->userLpaActorToken, $response['user-lpa-actor-token']);
            assertEquals($this->lpaUid, $response['lpa']['uId']);
            assertEquals($status, $response['lpa']['status']);
        }
    }

    /**
     * @Then /^I should be able to click a link to go and create the access codes$/
     */
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes()
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
    }

    /**
     * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertArrayHasKey('Cancelled', $response);
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be taken back to the access code summary page/
     */
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet()
    {
        // Not needed for this context
    }

    /**
     * @When /^I view my dashboard$/
     */
    public function iViewMyDashboard()
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->base->userAccountId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $request = $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->setLastRequest($request);
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation()
    {
        // Not needed for this context
    }

    /**
     * @When /^I want to cancel the access code for an organisation$/
     */
    public function iWantToCancelTheAccessCodeForAnOrganisation()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I want to see the option to cancel the code$/
     */
    public function iWantToSeeTheOptionToCancelTheCode()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I will be told that I have already requested this and the date I should receive the letter by$/
     */
    public function iWillBeToldThatIHaveAlreadyRequestedThisAndTheDateIShouldReceiveTheLetterBy()
    {
        // Not needed for this context
    }

    /**
     * @When /^One of the generated access code has expired$/
     */
    public function oneOfTheGeneratedAccessCodeHasExpired()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // not needed for this context
    }

    /**
     * @Then /^The full LPA is displayed with the correct (.*)$/
     */
    public function theFullLPAIsDisplayedWithTheCorrect($message)
    {
        // Not needed for this context
    }

    /**
     * @Given /^The LPA has not been added$/
     */
    public function theLPAHasNotBeenAdded()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);

        $response = $this->getResponseAsJson();

        assertEmpty($response['data']);
    }

    /**
     * @Then /^The LPA is not found and I am told it was a bad request$/
     */
    public function theLPAIsNotFoundAndIAmToldItWasABadRequest()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);

        $response = $this->getResponseAsJson();

        assertEmpty($response['data']);
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->userLpaActorToken = '13579';
        $now = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');

        // codes api service call
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/validate')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['actor' => $this->actorId])));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // called twice
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // UserLpaActorMap::create
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userLpaActorToken,
                            'UserId' => $this->base->userAccountId,
                            'SiriusUid' => $this->lpaUid,
                            'ActorId' => $this->actorId,
                            'Added' => $now,
                        ]
                    ),
                ]
            )
        );

        // codes api service call
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/revoke')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->apiPost(
            '/v1/add-lpa/confirm',
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
            ],
            [
                'user-token' => $this->userLpaActorToken,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CREATED);

        $response = $this->getResponseAsJson();
        assertNotNull($response['user-lpa-actor-token']);
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click to check the access codes$/
     */
    public function iClickToCheckTheAccessCodes()
    {
        $code1 = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2020-01-01T00:00:00Z',
            'Expires' => '2020-12-01T00:00:00Z',
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode' => $this->accessCode,
            'Viewed' => false,
        ];

        $code2 = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2020-01-01T00:00:00Z',
            'Expires' => '2020-12-01T00:00:00Z',
            'UserLpaActor' => '65d6833a-66d3-430f-8cf6-9e4fb1d851f1',
            'Organisation' => 'SomeOrganisation',
            'ViewerCode' => 'B97LRK3U68PE',
            'Viewed' => false,
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
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertArrayHasKey($this->userLpaActorToken, $response);
        assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => '65d6833a-66d3-430f-8cf6-9e4fb1d851f1',
                            'ActorId' => '4455',
                            'UserId' => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
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

        assertCount(2, $response);

        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['ViewerCode'], $this->accessCode);
        assertEquals($response[0]['ActorId'], $this->actorId);

        assertEquals($response[1]['Organisation'], 'SomeOrganisation');
        assertEquals($response[1]['SiriusUid'], '700000000054');
        assertEquals($response[1]['UserLpaActor'], '65d6833a-66d3-430f-8cf6-9e4fb1d851f1');
        assertEquals($response[1]['ViewerCode'], 'B97LRK3U68PE');
        assertEquals($response[1]['ActorId'], '4455');
    }

    /**
     * @Given /^Co\-actors have also created access codes for the same LPA$/
     */
    public function coActorsHaveAlsoCreatedAccessCodesForTheSameLPA()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see all of the access codes and their details$/
     */
    public function iCanSeeAllOfTheAccessCodesAndTheirDetails()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA$/
     */
    public function iClickToCheckMyAccessCodesThatIsUsedToViewLPA()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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
                                [
                                    'Viewed' => '2020-10-01T23:59:59+00:00',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy' => 'organisation1'
                                ],
                                [
                                    'Viewed' => '2020-10-01T23:59:59+00:00',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy' => 'organisation2'
                                ]
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
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorId,
                            'UserId' => $this->userId,
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

        assertArrayHasKey('Viewed', $response[0]);
        assertEquals($response[0][0]['ViewerCode'], $this->accessCode);
        assertEquals($response[0][0]['ViewedBy'], 'organisation1');
        assertEquals($response[0][1]['ViewedBy'], 'organisation2');
    }

    /**
     * @Then /^The LPA is removed$/
     */
    public function theLpaIsRemoved()
    {
        $actorToken = 'token123';
        $siriusUid = '700000001';
        $added = '2020-08-20';
        $actorId = '59';
        $userId = 'user123';

        // userLpaActorMapRepository::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => $actorToken,
                'SiriusUid' => $siriusUid,
                'Added' => $added,
                'ActorId' => $actorId,
                'UserId' => $userId
            ])
        ]));

        //viewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => '1',
                                'ViewerCode' => '123ABCD6789',
                                'SiriusUid' => '700000055554',
                                'Added' => '2021-01-01 00:00:00',
                                'Expires' => '2021-02-01 00:00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                            ]
                        ),
                    ],
                ]
            )
        );
        //viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'Expires' => '2022-01-05 12:34:56',
                                'UserLpaActor' => '',
                                'Organisation' => $this->organisation,
                                'ViewerCode' => '123ABCD6789',
                                'Viewed' => false,
                            ]
                        ),
                    ],
                ]
            )
        );

        // userLpaActorMapRepository::delete
        $this->awsFixtures->append(new Result([]));

        $this->apiDelete('/v1/lpas/' . $actorToken);
    }

    /**
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheAddAnOlderLPAPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // check if actor has a code
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/exists')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Created' => null
                        ]
                    )
                )
            );

        // request a code to be generated and letter to be sent
        $this->apiFixtures->post('/v1/use-an-lpa/lpas/requestCode')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NO_CONTENT,
                    []
                )
            );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => $this->lpaUid,
                'first_names'       => $this->userFirstnames,
                'last_name'         => $this->userSurname,
                'dob'               => $this->userDob,
                'postcode'          => $this->userPostCode
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * @Given /^I confirm that those details are correct$/
     */
    public function iConfirmThatThoseDetailsAreCorrect()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide the details from a valid paper LPA document$/
     */
    public function iProvideTheDetailsFromAValidPaperLPADocument()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide details of an LPA that does not exist$/
     */
    public function iProvideDetailsOfAnLPAThatDoesNotExist()
    {
        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode($this->lpa)
                )
            );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => '700000004321',
                'first_names'       => $this->userFirstnames,
                'last_name'         => $this->userSurname,
                'dob'               => $this->userDob,
                'postcode'          => $this->userPostCode
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    /**
     * @When /^I provide details "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" that do not match the paper document$/
     */
    public function iProvideDetailsThatDoNotMatchThePaperDocument($firstnames, $lastname, $postcode, $dob)
    {
        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => $this->lpaUid,
                'first_names'       => $firstnames,
                'last_name'         => $lastname,
                'dob'               => $dob,
                'postcode'          => $postcode
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide details from an LPA registered before Sept 2019$/
     */
    public function iProvideDetailsFromAnLPARegisteredBeforeSept2019()
    {
        $this->lpa->registrationDate = '2019-08-31';

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => $this->lpaUid,
                'first_names'       => $this->userFirstnames,
                'last_name'         => $this->userSurname,
                'dob'               => $this->userDob,
                'postcode'          => $this->userPostCode
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    /**
     * @Then /^I am told that I cannot request an activation key$/
     */
    public function iAmToldThatICannotRequestAnActivationKey()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide the details from a valid paper document that already has an activation key$/
     */
    public function iProvideTheDetailsFromAValidPaperDocumentThatAlreadyHasAnActivationKey()
    {
        $createdDate = (new DateTime())->modify('-14 days')->format('Y-m-d');

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // check if actor has a code
        $this->apiFixtures->post('http://lpa-codes-pact-mock/v1/exists')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Created' => $createdDate])));

        // API call to request an activation key
        $this->apiPatch(
            '/v1/lpas/request-letter',
            [
                'reference_number'  => $this->lpaUid,
                'first_names'       => $this->userFirstnames,
                'last_name'         => $this->userSurname,
                'dob'               => $this->userDob,
                'postcode'          => $this->userPostCode
            ],
            [
                'user-token' => $this->userId,
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->ui->assertSession()->responseContains('LPA not eligible as an activation key already exists');
        $this->ui->assertSession()->responseContains('activation_key_created');
        $this->ui->assertSession()->responseContains($createdDate);
    }

    /**
     * @Then /^I am told that I have an activation key for this LPA and where to find it$/
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt()
    {
        // Not needed for this context
    }

    /**
     * @Given /^A malformed request is sent which is missing a data attribute$/
     */
    public function aMalformedRequestIsSentWhichIsMissingADataAttribute()
    {
        $dataAttributes = [
            'reference_number'  => $this->lpaUid,
            'first_names'       => $this->userFirstnames,
            'last_name'         => $this->userSurname,
            'dob'               => $this->userDob,
            'postcode'          => $this->userPostCode
        ];

        foreach ($dataAttributes as $name => $value) {
            $dataAttributes[$name] = null;

            // API call to request an activation key
            $this->apiPatch(
                '/v1/lpas/request-letter',
                $dataAttributes,
                [
                    'user-token' => $this->userId,
                ]
            );

            $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @Then /^I am told that something went wrong$/
     */
    public function iAmToldThatSomethingWentWrong()
    {
        // Not needed for this context
    }

    /**
     * @Given /^The status of the LPA changed from Registered to Suspended$/
     */
    public function theStatusOfTheLPAChangedFromRegisteredToSuspended()
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
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
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

        assertEmpty($response);
    }
}
