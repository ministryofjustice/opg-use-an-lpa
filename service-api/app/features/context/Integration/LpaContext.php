<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\AddLpa;
use App\Service\Lpa\AddOlderLpa;
use App\Service\Lpa\RemoveLpa;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\OlderLpaService;
use App\Service\ViewerCodes\ViewerCodeService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use BehatTest\Context\UsesPactContextTrait;
use DateTime;
use DateTimeZone;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;

/**
 * Class LpaContext
 *
 * A place for context steps relating to LPA interactions such as adding, removing etc.
 *
 * @package BehatTest\Context\Integration
 *
 * @property mixed        lpa
 * @property string       oneTimeCode
 * @property string       lpaUid
 * @property string       userLpaActorToken
 * @property string|array $userDob
 * @property string       actorLpaId
 * @property string       userId
 * @property string       organisation
 * @property string       accessCode
 * @property string       userPostCode
 * @property string       userSurname
 * @property string       userFirstname
 */
class LpaContext extends BaseIntegrationContext
{
    use SetupEnv;
    use UsesPactContextTrait;

    private MockHandler $apiFixtures;
    private string $apiGatewayPactProvider;
    private AwsMockHandler $awsFixtures;
    private string $codesApiPactProvider;
    private RemoveLpa $deleteLpa;
    private LpaService $lpaService;

    /**
     * @Then /^a letter is requested containing a one time use code$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid' => (int)$this->lpaUid,
                'actor_uid' => (int)$this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            // Save activation key request in the DB
            $this->awsFixtures->append(new Result([]));
        }

        $olderLpaService = $this->container->get(OlderLpaService::class);

        try {
            $olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId, $this->userId);
        } catch (ApiException $exception) {
            throw new Exception('Failed to request access code letter');
        }
    }

    /**
     * @Then /^A record of my activation key request is saved$/
     */
    public function aRecordOfMyActivationKeyRequestIsSaved()
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        assertEquals($lastCommand->getName(), 'PutItem');
        assertEquals($lastCommand->toArray()['TableName'], 'user-actor-lpa-map');
        assertEquals($lastCommand->toArray()['Item']['SiriusUid'], ['S' => $this->lpaUid]);
        assertArrayHasKey('ActivateBy', $lastCommand->toArray()['Item']);
    }

    /**
     * @Then /^A record of my activation key request is not saved$/
     */
    public function aRecordOfMyActivationKeyRequestIsNotSaved()
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        assertNotEquals($lastCommand->getName(), 'PutItem');
    }

    /**
     * @Then /^A record of the LPA requested is saved to the database$/
     */
    public function aRecordOfTheLPARequestedIsSavedToTheDatabase()
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        assertArrayHasKey($this->userLpaActorToken, $lpa);
        assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);
    }

    /**
     * @Given /^Co\-actors have also created access codes for the same LPA$/
     */
    public function coActorsHaveAlsoCreatedAccessCodesForTheSameLPA()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codeData = $viewerCodeService->addCode($this->userLpaActorToken, $this->userId, $this->organisation);

        $codeExpiry = (new DateTime($codeData['expires']))->format('Y-m-d');
        $in30Days = (new DateTime('23:59:59 +30 days', new DateTimeZone('Europe/London')))->format('Y-m-d');

        assertArrayHasKey('code', $codeData);
        assertNotNull($codeData['code']);
        assertEquals($codeExpiry, $in30Days);
        assertEquals($codeData['organisation'], $this->organisation);
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not used in this context
    }

    /**
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheAddAnOlderLPAPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that I cannot request an activation key$/
     */
    public function iAmToldThatICannotRequestAnActivationKey()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that I have an activation key for this LPA and where to find it$/
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt()
    {
        // Not needed for this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
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
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addLpaService = $this->container->get(AddLpa::class);

        $expectedResponse = [
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ],
            'caseSubtype' => $this->lpa->caseSubtype,
            'lpaActorToken' => $this->userLpaActorToken
        ];

        try {
            $addLpaService->validateAddLpaData(
                [
                    'actor-code' => $this->oneTimeCode,
                    'uid' => $this->lpaUid,
                    'dob' => $this->userDob,
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA already added', $ex->getMessage());
            assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA already added exception should have been thrown');
    }

    /**
     * @When /^I provide the attorney details from a valid paper LPA document$/
     */
    public function iProvideTheAttorneyDetailsFromAValidPaperLPADocument()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'  => $this->lpa->uId,
            'dob'               => $this->lpa->attorneys[0]->dob,
            'postcode'          => $this->lpa->attorneys[0]->addresses[0]->postcode,
            'first_names'       => $this->lpa->attorneys[0]->firstname,
            'last_name'         => $this->lpa->attorneys[0]->surname,
            'force_activation_key' => false
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(new Result([]));

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpa->uId,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $codeExists->Created = null;

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $this->lpa->uId,
                'actor' => $this->lpa->attorneys[0]->uId,
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'     => json_decode(json_encode($this->lpa->attorneys[0]), true),
            'role'      => 'attorney',
            'lpa-id'    => $this->lpa->uId,
            'caseSubtype'    => $this->lpa->caseSubtype,
            'donor'          => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ],
            'attorney' => [
                'uId'           => $this->lpa->attorneys[0]->uId,
                'firstname'     => $this->lpa->attorneys[0]->firstname,
                'middlenames'   => $this->lpa->attorneys[0]->middlenames,
                'surname'       => $this->lpa->attorneys[0]->surname,
            ],
        ];

        assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @Then /^I being the attorney on the LPA I am shown the donor details$/
     */
    public function iBeingTheAttorneyOnTheLpaIAmShownTheDonorDetails()
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

        // The underlying SmartGamma library has a very naive match processor for
        // passed in response values and will assume lpaUid's and actorLpaId's are integers.
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa' => $this->lpaUid,
                'dob' => $this->userDob,
                'code' => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => $this->actorLpaId,
            ],
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addLpaService = $this->container->get(AddLpa::class);

        try {
            $addLpaService->validateAddLpaData(
                [
                    'actor-code' => $this->oneTimeCode,
                    'uid' => $this->lpaUid,
                    'dob' => $this->userDob,
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA status is not registered', $ex->getMessage());
            return;
        }
        throw new ExpectationFailedException('Exception should have been thrown due to invalid LPA status');
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

        // The underlying SmartGamma library has a very naive match processor for
        // passed in response values and will assume lpaUid's and actorLpaId's are integers.
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa' => $this->lpaUid,
                'dob' => $this->userDob,
                'code' => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => $this->actorLpaId,
            ],
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_NOT_FOUND,
        );

        $addLpaService = $this->container->get(AddLpa::class);

        try {
            $addLpaService->validateAddLpaData(
                [
                    'actor-code' => $this->oneTimeCode,
                    'uid' => $this->lpaUid,
                    'dob' => $this->userDob,
                ],
                $this->userId
            );
        } catch (NotFoundException $ex) {
            assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            assertEquals('Code validation failed', $ex->getMessage());
            return;
        }
        throw new ExpectationFailedException('LPA should not have been found');
    }

    /**
     * @When /^I request to add an LPA that I have requested an activation key for$/
     */
    public function iRequestToAddAnLPAThatIHaveRequestedAnActivationKeyFor()
    {
        //UserLpaActorMap: getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp()
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
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                            'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp()
                        ]
                    ),
                ]
            )
        );

        // lpaService: getByUserLpaActorToken
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        // codes api service call
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa'   => $this->lpaUid,
                'dob'   => $this->userDob,
                'code'  => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => $this->actorLpaId,
            ],
        );

        $addLpaService = $this->container->get(AddLpa::class);

        $validatedLpa = $addLpaService->validateAddLpaData(
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            $this->userId
        );

        assertArrayHasKey('actor', $validatedLpa);
        assertArrayHasKey('lpa', $validatedLpa);
        assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);
    }

    /**
     * @Given /^The activateBy TTL is removed from the record in the DB$/
     */
    public function theActivateByTTLIsRemovedFromTheRecordInTheDB()
    {
        //UserLpaActorMapRepository: getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp()
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
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                            'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp()
                        ]
                    ),
                ]
            )
        );

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/revoke',
            [
                'code' => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [],
        );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        try {
            $response = $actorCodeService->confirmDetails(
                $this->oneTimeCode,
                $this->lpaUid,
                $this->userDob,
                (string)$this->actorLpaId
            );
        } catch (Exception $ex) {
            throw new Exception('Lpa confirmation unsuccessful');
        }

        assertEquals($this->userLpaActorToken, $response);
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
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
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode' => $this->accessCode,
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
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        assertArrayHasKey($this->userLpaActorToken, $lpa);
        assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

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
                            'ActorId' => $this->actorLpaId,
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

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertCount(2, $codes);
        assertEquals($codes[0], $code1);
        assertEquals($codes[1], $code2);

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // This response is duplicated for the 2nd code

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(ViewerCodeActivity::class);
        $codesWithStatuses = $viewerCodeService->getStatusesForViewerCodes($codes);
        assertCount(2, $codesWithStatuses);

        // Loop for asserting on both the 2 codes returned
        for ($i = 0; $i < 2; $i++) {
            assertEquals($codesWithStatuses[$i]['SiriusUid'], $this->lpaUid);
            assertEquals($codesWithStatuses[$i]['UserLpaActor'], $this->userLpaActorToken);
            assertEquals($codesWithStatuses[$i]['Organisation'], $this->organisation);
            assertEquals($codesWithStatuses[$i]['ViewerCode'], $this->accessCode);

            if ($i == 0) {
                assertEquals(
                    $codesWithStatuses[$i]['Expires'],
                    (new DateTime())->modify($code1Expiry)->format('Y-m-d')
                );
            } else {
                assertEquals(
                    $codesWithStatuses[$i]['Expires'],
                    (new DateTime())->modify($code2Expiry)->format('Y-m-d')
                );
            }
        }

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $userLpaActorMap = $this->container->get(UserLpaActorMap::class);
        $lpa = $userLpaActorMap->get($this->userLpaActorToken);

        assertEquals($lpa['SiriusUid'], $this->lpaUid);
        assertEquals($lpa['Id'], $this->userLpaActorToken);
        assertEquals($lpa['ActorId'], $this->actorLpaId);
        assertEquals($lpa['UserId'], $this->userId);
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
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
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        assertArrayHasKey($this->userLpaActorToken, $lpa);
        assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

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
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertEmpty($codes);
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId' => $this->actorLpaId,
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
                                'Expires' => '2021-01-05 12:34:56',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Expires'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA/
     */
    public function iClickToCheckMyAccessCodeThatIsUsedToViewLPA()
    {
        //Get the LPA
        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId' => $this->actorLpaId,
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
                                'Added' => '2020-01-05 12:34:56',
                                'Expires' => '2021-01-05 12:34:56',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Expires'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I check my access codes$/
     */
    public function iClickToCheckMyAccessCodes()
    {
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId' => $this->actorLpaId,
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
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        // actor id  does not match the userId returned

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Added'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I click to check the access codes$/
     */
    public function iClickToCheckTheAccessCodes()
    {
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'Expires' => '2022-01-05 12:34:56',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => [
                                    0 => [
                                        'Viewed' => '2020-10-01T23:59:59+00:00',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => 'organisation1',
                                    ],
                                    1 => [
                                        'Viewed' => '2020-10-20T23:59:59+00:00',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => 'organisation2',
                                    ],
                                ],
                            ]
                        ),
                    ],
                ]
            )
        );
        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        // actor id  does not match the userId returned
        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());
        $viewerCodeService = $this->container->get(ViewerCodeActivity::class);
        $codesWithStatuses = $viewerCodeService->getStatusesForViewerCodes($accessCodes);

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        foreach ($codesWithStatuses as $key => $viewerCode) {
            if (!empty($viewerCode['UserLpaActor'])) {
                $userLpaActorMap = $this->container->get(UserLpaActorMap::class);
                $codeOwner = $userLpaActorMap->get($viewerCode['UserLpaActor']);
                $codesWithStatuses[$key]['ActorId'] = $codeOwner['ActorId'];
            }
        }

        assertEquals($codesWithStatuses[0]['Organisation'], $this->organisation);
        assertEquals($codesWithStatuses[0]['SiriusUid'], $this->lpaUid);
        assertEquals($codesWithStatuses[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($codesWithStatuses[0]['ViewerCode'], $this->accessCode);
        assertEquals($codesWithStatuses[0]['ActorId'], $lpaData['actor']['details']['uId']);
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
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
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2020-01-05 12:34:56',
                                'Expires' => '2021-01-05 12:34:56',
                                'Cancelled' => '2020-01-15',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                            ]
                        ),
                    ],
                ]
            )
        );

        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codeData = $viewerCodeService->cancelCode($this->userLpaActorToken, $this->userId, $this->accessCode);

        assertEmpty($codeData);
    }

    /**
     * @Given /^I confirm details shown to me of the found LPA are correct$/
     */
    public function iConfirmDetailsShownToMeOfTheFoundLPAAreCorrect()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'  => $this->lpa->uId,
            'dob'               => $this->lpa->donor->dob,
            'postcode'          => $this->lpa->donor->addresses[0]->postcode,
            'first_names'       => $this->lpa->donor->firstname,
            'last_name'         => $this->lpa->donor->surname,
            'force_activation_key' => true
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // pact interaction failed so had to use apiFixtures
        $this->apiFixtures
            ->get('/v1/use-an-lpa/lpas/' . $this->lpa->uId)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'     => json_decode(json_encode($this->lpa->donor), true),
            'role'      => 'donor',
            'lpa-id'    => $this->lpa->uId,
            'caseSubtype'    => $this->lpa->caseSubtype,
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ]
        ];

        assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @Given /^I confirm the details I provided are correct$/
     * @Then /^I confirm details shown to me of the found LPA are correct$/
     * @Given /^I provide the details from a valid paper document$/
     * @Then /^I am shown the details of an LPA$/
     * @Then /^I am asked for my contact details$/
     * @Then /^I being the donor on the LPA I am not shown the attorney details$/
     */
    public function iAmShownTheDetailsOfAnLPA()
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
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument()
    {
        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->userPostCode = 'string';
        $this->userFirstname = 'Ian Deputy';
        $this->userSurname = 'Deputy';
        $this->lpa->registrationDate = '2019-09-01';
        $this->userDob = '1975-10-05';
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
        $this->actorLpaId = '700000000054';
        $this->userId = '9999999999';
        $this->userLpaActorToken = '111222333444';
    }

    /**
     * @Given /^I have created an access code$/
     */
    public function iHaveCreatedAnAccessCode()
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
        $this->iAmGivenAUniqueAccessCode();
    }

    /**
     * @Given /^I have generated an access code for an organisation and can see the details$/
     */
    public function iHaveGeneratedAnAccessCodeForAnOrganisationAndCanSeeTheDetails()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iClickToCheckMyAccessCodes();
        $this->iCanSeeAllOfMyAccessCodesAndTheirDetails();
    }

    /**
     * @Given /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide details from an LPA registered before Sept 2019$/
     */
    public function iProvideDetailsFromAnLPARegisteredBeforeSept2019()
    {
        $this->lpa->registrationDate = '2019-08-31';

        $data = [
            'reference_number' => $this->lpaUid,
            'dob' => $this->userDob,
            'postcode' => $this->userPostCode,
            'first_names' => $this->userFirstname,
            'last_name' => $this->userSurname,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA not eligible due to registration date', $ex->getMessage());
            return;
        }
    }

    /**
     * @When /^I provide details of an LPA that does not exist$/
     */
    public function iProvideDetailsOfAnLPAThatDoesNotExist()
    {
        $invalidLpaId = '700000004321';

        $data = [
            'reference_number' => $invalidLpaId,
            'dob' => $this->userDob,
            'postcode' => $this->userPostCode,
            'first_names' => $this->userFirstname,
            'last_name' => $this->userSurname,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $invalidLpaId,
            StatusCodeInterface::STATUS_NOT_FOUND,
            []
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (NotFoundException $ex) {
            assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            assertEquals('LPA not found', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('LPA should not have been found');
    }

    /**
     * @When /^I provide details "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" that do not match the paper document$/
     */
    public function iProvideDetailsThatDoNotMatchThePaperDocument($firstnames, $lastname, $postcode, $dob)
    {
        $data = [
            'reference_number' => $this->lpaUid,
            'dob' => $dob,
            'postcode' => $postcode,
            'first_names' => $firstnames,
            'last_name' => $lastname,
            'force_activation_key' => false
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA details do not match', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('LPA should not have matched data provided');
    }

    /**
     * @When /^I provide the details from a valid paper LPA document$/
     */
    public function iProvideTheDetailsFromAValidPaperLPADocument()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'  => $this->lpa->uId,
            'dob'               => $this->lpa->donor->dob,
            'postcode'          => $this->lpa->donor->addresses[0]->postcode,
            'first_names'       => $this->lpa->donor->firstname,
            'last_name'         => $this->lpa->donor->surname,
            'force_activation_key' => false
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpa->uId,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $codeExists->Created = null;

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $this->lpa->uId,
                'actor' => $this->lpa->donor->uId,
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);
        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'     => json_decode(json_encode($this->lpa->donor), true),
            'role'      => 'donor',
            'lpa-id'    => $this->lpa->uId,
            'caseSubtype'    => $this->lpa->caseSubtype,
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ]
        ];

        assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @When /^I provide the details from a valid paper document that already has an activation key$/
     */
    public function iProvideTheDetailsFromAValidPaperDocumentThatAlreadyHasAnActivationKey()
    {
        $data = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => $this->userDob,
            'postcode'              => $this->userPostCode,
            'first_names'           => $this->userFirstname,
            'last_name'             => $this->userSurname,
            'force_activation_key'  => false
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $createdDate = (new DateTime())->modify('-14 days')->format('Y-m-d');
        $codeExists->Created = $createdDate;

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa' => $this->lpaUid,
                'actor' => $this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA has an activation key already', $ex->getMessage());
            assertEquals(
                [
                    'donor'         => [
                        'uId'           => $this->lpa->donor->uId,
                        'firstname'     => $this->lpa->donor->firstname,
                        'middlenames'   => $this->lpa->donor->middlenames,
                        'surname'       => $this->lpa->donor->surname,
                    ],
                    'caseSubtype'   => $this->lpa->caseSubtype
                ],
                $ex->getAdditionalData()
            );
            return;
        }

        throw new ExpectationFailedException('Activation key exists exception should have been thrown');
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // The underlying SmartGamma library has a very naive match processor for
        // passed in response values and will assume lpaUid's and actorLpaId's are integers.
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa'   => $this->lpaUid,
                'dob'   => $this->userDob,
                'code'  => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => $this->actorLpaId,
            ],
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addLpaService = $this->container->get(AddLpa::class);

        $validatedLpa = $addLpaService->validateAddLpaData(
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            $this->userId
        );

        assertArrayHasKey('actor', $validatedLpa);
        assertArrayHasKey('lpa', $validatedLpa);
        assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = 'TestOrg';
        $this->accessCode = 'XYZ321ABC987';

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'     => $this->lpaUid,
                            'Added'         => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'            => $this->userLpaActorToken,
                            'ActorId'       => $this->actorLpaId,
                            'UserId'        => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodes::add
        $this->awsFixtures->append(new Result());
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
                            'SiriusUid'     => $this->lpaUid,
                            'Added'         => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'            => $this->userLpaActorToken,
                            'ActorId'       => $this->actorLpaId,
                            'UserId'        => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        // LpaService::getLpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                )
            );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, $this->userId);

        if ($status == "Revoked") {
            assertEmpty($lpaData);
        } else {
            assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
            assertEquals($this->lpa->id, $lpaData['lpa']['id']);
            assertEquals($this->lpa->status, $lpaData['lpa']['status']);
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
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithStatus()
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
        $lpas = $this->lpaService->getAllForUser($this->userId);

        assertEmpty($lpas);
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->oneTimeCode, $this->lpaUid, $this->userDob);

        assertNull($validatedLpa);
    }

    /**
     * @Then /^The LPA is removed and my active codes are cancelled$/
     */
    public function theLPAIsRemovedAndMyActiveCodesAreCancelled()
    {
        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid'     => $this->lpaUid,
                            'Added'         => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'            => $this->userLpaActorToken,
                            'ActorId'       => $this->actorLpaId,
                            'UserId'        => $this->userId,
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
                                'Id'            => '2',
                                'ViewerCode'    => 'YG41BCD693FH',
                                'SiriusUid'     => $this->lpaUid,
                                'Added'         => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                                'Expires'       => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                                'UserLpaActor'  => $this->userLpaActorToken,
                                'Organisation'  => 'Some Organisation 2',
                            ]
                        ),
                        $this->marshalAwsResultData( // 3rd code has already been cancelled
                            [
                                'Id'            => '3',
                                'ViewerCode'    => 'RL2AD1936KV2',
                                'SiriusUid'     => $this->lpaUid,
                                'Added'         => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                                'Expires'       => (new DateTime())->modify('-1 month')->format('Y-m-d'),
                                'Cancelled'     => (new DateTime())->modify('-2 months')->format('Y-m-d'),
                                'UserLpaActor'  => $this->userLpaActorToken,
                                'Organisation'  => 'Some Organisation 3',
                            ]
                        ),
                    ],
                ]
            )
        );

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result());
        // viewerCodesRepository::cancel
        $this->awsFixtures->append(new Result()); // 1st code is active therefore is cancelled

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result()); // 2nd code has expired therefore isn't cancelled

        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(new Result()); // 3rd code has already been cancelled

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $lpaRemoved = ($this->deleteLpa)($this->userId, $this->userLpaActorToken);

        assertEquals($this->lpa->uId, $lpaRemoved['uId']);
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $now = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');
        $this->userLpaActorToken = '13579';

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([]));
        // UserLpaActorMap::create
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => [
                        $this->marshalAwsResultData(
                            [
                                'Id'        => $this->userLpaActorToken,
                                'UserId'    => $this->userId,
                                'SiriusUid' => $this->lpaUid,
                                'ActorId'   => $this->actorLpaId,
                                'Added'     => $now,
                            ]
                        ),
                    ],
                ]
            )
        );

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/revoke',
            [
                'code' => $this->oneTimeCode,
            ],
            StatusCodeInterface::STATUS_OK,
            [],
        );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        try {
            $response = $actorCodeService->confirmDetails(
                $this->oneTimeCode,
                $this->lpaUid,
                $this->userDob,
                (string)$this->actorLpaId
            );
        } catch (Exception $ex) {
            throw new Exception('Lpa confirmation unsuccessful');
        }

        assertNotNull($response);
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        // Not needed for this context
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
        $this->lpaService = $this->container->get(LpaService::class);
        $this->deleteLpa = $this->container->get(RemoveLpa::class);

        $config = $this->container->get('config');
        $this->codesApiPactProvider = parse_url($config['codes_api']['endpoint'], PHP_URL_HOST);
        $this->apiGatewayPactProvider = parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST);
    }

    /**
     * @Given /^The status of the LPA changed from Registered to Suspended$/
     */
    public function theStatusOfTheLPAChangedFromRegisteredToSuspended()
    {
        $this->lpa->status = 'Suspended';

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

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
                                'ActorId'   => $this->actorLpaId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        assertEmpty($lpa);
    }

    /**
     * @When /^The status of the LPA got Revoked$/
     */
    public function theStatusOfTheLpaGotRevoked()
    {
        // Not needed for this context
    }

    /**
     * @When /^I check my access codes of the status changed LPA$/
     * @When /^I request to give an organisation access to the LPA whose status changed to Revoked$/
     */
    public function iCheckMyAccessCodesOfTheStatusChangedLpa()
    {
        $this->lpa->status = "Revoked";

        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertEmpty($lpaData);
    }

    /**
     * @Given /^I lost the letter containing my activation key$/
     */
    public function iLostTheLetterContainingMyActivationKey()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should have an option to regenerate an activation key for the old LPA I want to add$/
     */
    public function iShouldHaveAnOptionToRegenerateAnActivationKeyForTheOldLPAIWantToAdd()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request for a new activation key again$/
     */
    public function iRequestForANewActivationKeyAgain()
    {
        $data = [
            'reference_number'      => $this->lpa->uId,
            'first_names'           => $this->lpa->donor->firstname . ' ' . $this->lpa->donor->middlenames,
            'last_name'             => $this->lpa->donor->surname,
            'dob'                   => $this->lpa->donor->dob,
            'postcode'              => $this->lpa->donor->addresses[0]->postcode,
            'force_activation_key'  => true
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);
        $response = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'     => json_decode(json_encode($this->lpa->donor), true),
            'role'      => 'donor',
            'lpa-id'    => $this->lpa->uId,
            'caseSubtype'    => $this->lpa->caseSubtype,
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ]
        ];

        assertEquals($expectedResponse, $response);
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already added to my account$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyAddedToMyAccount()
    {
        $differentLpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'      => $this->lpaUid,
            'dob'                   => $this->userDob,
            'postcode'              => $this->userPostCode,
            'first_names'           => $this->userFirstname,
            'last_name'             => $this->userSurname,
            'force_activation_key'  => false
        ];

        // UserLpaActorMap::getAllForUser / getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $differentLpa->uId,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => 'abcd-12345-efgh',
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        if (($this->container->get(FeatureEnabled::class)('save_older_lpa_requests'))) {
            // LpaService::getByUserLpaActorToken
            $this->awsFixtures->append(
                new Result(
                    [
                        'Item' => $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ]
                )
            );
        }

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        if (!($this->container->get(FeatureEnabled::class)('save_older_lpa_requests'))) {
            // LpaRepository::get
            $this->pactGetInteraction(
                $this->apiGatewayPactProvider,
                '/v1/use-an-lpa/lpas/' . $differentLpa->uId,
                StatusCodeInterface::STATUS_OK,
                $differentLpa
            );
        }

        $expectedResponse = [
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ],
            'caseSubtype' => $this->lpa->caseSubtype,
            'lpaActorToken' => $this->userLpaActorToken
        ];

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA already added', $ex->getMessage());
            assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA already added exception should have been thrown');
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already requested an activation key for$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor()
    {
        $data = [
            'reference_number' => $this->lpaUid,
            'dob' => $this->userDob,
            'postcode' => $this->userPostCode,
            'first_names' => $this->userFirstname,
            'last_name' => $this->userSurname,
            'force_activation_key' => false
        ];

        // UserLpaActorMap::getAllForUser / getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id' => $this->userLpaActorToken,
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                                'ActivateBy' => 123456789
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
                            'SiriusUid' => $this->lpaUid,
                            'Added' => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id' => $this->userLpaActorToken,
                            'ActorId' => $this->actorLpaId,
                            'UserId' => $this->userId,
                            'ActivateBy' => 123456789
                        ]
                    ),
                ]
            )
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $expectedResponse = [
            'donor'         => [
                'uId'           => $this->lpa->donor->uId,
                'firstname'     => $this->lpa->donor->firstname,
                'middlenames'   => $this->lpa->donor->middlenames,
                'surname'       => $this->lpa->donor->surname,
            ],
            'caseSubtype' => $this->lpa->caseSubtype
        ];

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA has an activation key already', $ex->getMessage());
            assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA has an activation key already exception should have been thrown');
    }

    /**
     * @When I provide details of an LPA that is not registered
     */
    public function iProvideDetailsDetailsOfAnLpaThatIsNotRegistered()
    {
        $this->lpa->status = 'Pending';

        $data = [
            'reference_number'  => $this->lpaUid,
            'dob'               => $this->userDob,
            'postcode'          => $this->userPostCode,
            'first_names'       => $this->userFirstname,
            'last_name'         => $this->userSurname,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (NotFoundException $ex) {
            assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            assertEquals('LPA status invalid', $ex->getMessage());
            return;
        }
    }

    /**
     * @When /^System recognises the Lpa is not cleansed$/
     */
    public function systemRecognisesTheLpaIsNotCleansed()
    {
        $data = [
            'reference_number'  => $this->lpaUid,
            'dob'               => $this->userDob,
            'postcode'          => $this->userPostCode,
            'first_names'       => $this->userFirstname,
            'last_name'         => $this->userSurname,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $codeExists->Created = null;

        $olderLpaService = $this->container->get(OlderLpaService::class);
        $lpaMatchResponse = $olderLpaService->checkLPAMatchAndGetActorDetails($this->userId, $data);

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $lpaMatchResponse['lpa-id'],
                'actor' => $lpaMatchResponse['actor-id'],
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $hasActivationCodeResponse = $olderLpaService->hasActivationCode(
            $lpaMatchResponse['lpa-id'],
            $lpaMatchResponse['actor-id']
        );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            //TO CHANGE
            $olderLpaService->checkIfLpaIsCleansed($lpaMatchResponse);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA is not cleansed', $ex->getMessage());
            return;
        }

        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid' => (int)$this->lpaUid,
                'actor_uid' => (int)$this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        $olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId, $this->userId);
    }

    /**
     * @When /^System recognises the Lpa is cleansed$/
     */
    public function systemRecognisesTheLpaIsCleansed()
    {
        $data = [
            'reference_number'  => $this->lpaUid,
            'dob'               => $this->userDob,
            'postcode'          => $this->userPostCode,
            'first_names'       => $this->userFirstname,
            'last_name'         => $this->userSurname,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $codeExists->Created = null;

        $olderLpaService = $this->container->get(OlderLpaService::class);
        $lpaMatchResponse = $olderLpaService->checkLPAMatchAndGetActorDetails($this->userId, $data);

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $lpaMatchResponse['lpa-id'],
                'actor' => $lpaMatchResponse['actor-id'],
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $hasActivationCodeResponse = $olderLpaService->hasActivationCode(
            $lpaMatchResponse['lpa-id'],
            $lpaMatchResponse['actor-id']
        );

        $lpaMatchResponse['lpaIsCleansed'] = true;
        $olderLpaService->checkIfLpaIsCleansed($lpaMatchResponse);

        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid' => (int)$this->lpaUid,
                'actor_uid' => (int)$this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        $olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId, $this->userId);
    }
}
