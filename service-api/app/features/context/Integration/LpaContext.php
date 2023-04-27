<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Exception\ApiException;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Features\FeatureEnabled;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\AccessForAllLpaService;
use App\Service\Lpa\AddAccessForAllLpa;
use App\Service\Lpa\AddLpa;
use App\Service\Lpa\LpaService;
use App\Service\Lpa\RemoveLpa;
use App\Service\ViewerCodes\ViewerCodeService;
use Aws\CommandInterface;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use BehatTest\Context\UsesPactContextTrait;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\RequestInterface;
use stdClass;

use function PHPUnit\Framework\assertEquals;

/**
 * A place for context steps relating to LPA interactions such as adding, removing etc.
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
     * @Given I have previously requested the addition of a paper LPA to my account
     */
    public function iHavePreviouslyRequestedTheAdditionOfAPaperLPAToMyAccount(): void
    {
        // Not necessary for this context
    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     * @Then /^I am told my activation key is being sent$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode(): void
    {
        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid'  => (int)$this->lpaUid,
                'actor_uid' => (int)$this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            // Save activation key request in the DB
            $this->awsFixtures->append(new Result([]));
        }

        $olderLpaService = $this->container->get(AccessForAllLpaService::class);

        try {
            $olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId, $this->userId);
        } catch (ApiException $exception) {
            throw new Exception('Failed to request access code letter');
        }
    }

    /**
     * @Then /^a repeat request for a letter containing a one time use code is made$/
     */
    public function aRepeatRequestForALetterContainingAOneTimeUseCodeIsMade(): void
    {
        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid'  => (int)$this->lpaUid,
                'actor_uid' => (int)$this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            // Update activation key request in the DB
            $this->awsFixtures->append(new Result([]));
        }

        $olderLpaService = $this->container->get(AccessForAllLpaService::class);

        try {
            $olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId, $this->userId, '00-0-0-0-00');
        } catch (ApiException $exception) {
            throw new Exception('Failed to request access code letter');
        }
    }

    /**
     * @Then /^A record of my activation key request is saved$/
     */
    public function aRecordOfMyActivationKeyRequestIsSaved(): void
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertEquals($lastCommand->getName(), 'PutItem');
        Assert::assertEquals($lastCommand->toArray()['TableName'], 'user-actor-lpa-map');
        Assert::assertEquals($lastCommand->toArray()['Item']['SiriusUid'], ['S' => $this->lpaUid]);
        Assert::assertArrayHasKey('ActivateBy', $lastCommand->toArray()['Item']);
    }

    /**
     * @Then /^a record of my activation key request is updated/
     */
    public function aRecordOfMyActivationKeyRequestIsUpdated(): void
    {
        $dt = (new DateTime('now'))->add(new \DateInterval('P1Y'));

        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertEquals($lastCommand->getName(), 'UpdateItem');
        Assert::assertEquals($lastCommand->toArray()['TableName'], 'user-actor-lpa-map');
        Assert::assertEquals($lastCommand->toArray()['Key']['Id'], ['S' => '00-0-0-0-00']);
        Assert::assertEquals(
            intval($lastCommand->toArray()['ExpressionAttributeValues'][':a']['N']),
            $dt->getTimestamp(),
            '',
            5
        );
    }

    /**
     * @Then /^A record of my activation key request is not saved$/
     */
    public function aRecordOfMyActivationKeyRequestIsNotSaved(): void
    {
        $lastCommand = $this->awsFixtures->getLastCommand();
        Assert::assertNotEquals($lastCommand->getName(), 'PutItem');
    }

    /**
     * @Then /^A record of the LPA requested is saved to the database$/
     */
    public function aRecordOfTheLPARequestedIsSavedToTheDatabase(): void
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added'     => (new DateTime())->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => $this->userLpaActorToken,
                                'ActorId'   => $this->actorLpaId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        Assert::assertArrayHasKey($this->userLpaActorToken, $lpa);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);
    }

    /**
     * @Given /^Co\-actors have also created access codes for the same LPA$/
     */
    public function coActorsHaveAlsoCreatedAccessCodesForTheSameLPA(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode(): void
    {
        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codeData = $viewerCodeService->addCode($this->userLpaActorToken, $this->userId, $this->organisation);

        $codeExpiry = (new DateTime($codeData['expires']))->format('Y-m-d');
        $in30Days = (new DateTime('23:59:59 +30 days', new DateTimeZone('Europe/London')))->format('Y-m-d');

        Assert::assertArrayHasKey('code', $codeData);
        Assert::assertNotNull($codeData['code']);
        Assert::assertEquals($codeExpiry, $in30Days);
        Assert::assertEquals($codeData['organisation'], $this->organisation);
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage(): void
    {
        // Not used in this context
    }

    /**
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheAddAnOlderLPAPage(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that I cannot request an activation key$/
     * @Then /^I should expect it within 2 weeks time$/
     * @Then /^I should expect it within 4 weeks time$/
     * @Then /^I will receive an email confirming this information$/
     * @Given /^I provide the additional details asked$/
     * @Given /^I am asked to consent and confirm my details$/
     * @When /^I confirm that the data is correct and click the confirm and submit button$/
     */
    public function iAmToldThatICannotRequestAnActivationKey(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that I have an activation key for this LPA and where to find it$/
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain(): void
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

        $addLpaService = $this->container->get(AddLpa::class);

        $expectedResponse = [
            'donor' => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'caseSubtype'   => $this->lpa->caseSubtype,
            'lpaActorToken' => $this->userLpaActorToken,
        ];

        try {
            $addLpaService->validateAddLpaData(
                [
                    'actor-code' => $this->oneTimeCode,
                    'uid'        => $this->lpaUid,
                    'dob'        => $this->userDob,
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('LPA already added', $ex->getMessage());
            Assert::assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA already added exception should have been thrown');
    }

    /**
     * @When /^I provide the attorney details from a valid paper LPA document$/
     */
    public function iProvideTheAttorneyDetailsFromAValidPaperLPADocument(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'     => (int) $this->lpa->uId,
            'dob'                  => $this->lpa->attorneys[0]->dob,
            'postcode'             => $this->lpa->attorneys[0]->addresses[0]->postcode,
            'first_names'          => $this->lpa->attorneys[0]->firstname,
            'last_name'            => $this->lpa->attorneys[0]->surname,
            'force_activation_key' => false,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'       => json_decode(json_encode($this->lpa->attorneys[0]), true),
            'role'        => 'attorney',
            'lpa-id'      => $this->lpa->uId,
            'caseSubtype' => $this->lpa->caseSubtype,
            'donor'       => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'attorney'    => [
                'uId'         => $this->lpa->attorneys[0]->uId,
                'firstname'   => $this->lpa->attorneys[0]->firstname,
                'middlenames' => $this->lpa->attorneys[0]->middlenames,
                'surname'     => $this->lpa->attorneys[0]->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @Then /^I being the attorney on the LPA I am shown the donor details$/
     */
    public function iBeingTheAttorneyOnTheLpaIAmShownTheDonorDetails(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to add an LPA which has a status other than registered$/
     */
    public function iRequestToAddAnLPAWhichHasAStatusOtherThanRegistered(): void
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
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
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
                    'uid'        => $this->lpaUid,
                    'dob'        => $this->userDob,
                ],
                $this->userId
            );
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('LPA status is not registered', $ex->getMessage());
            return;
        }
        throw new ExpectationFailedException('Exception should have been thrown due to invalid LPA status');
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist(): void
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
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
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
                    'uid'        => $this->lpaUid,
                    'dob'        => $this->userDob,
                ],
                $this->userId
            );
        } catch (NotFoundException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            Assert::assertEquals('Code validation failed', $ex->getMessage());
            return;
        }
        throw new ExpectationFailedException('LPA should not have been found');
    }

    /**
     * @When /^I request to add an LPA that I have requested an activation key for$/
     */
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
                                'ActorId'    => $this->actorLpaId,
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
                            'ActorId'    => $this->actorLpaId,
                            'UserId'     => $this->userId,
                            'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
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
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
                'code' => $this->oneTimeCode,
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

        Assert::assertArrayHasKey('actor', $validatedLpa);
        Assert::assertArrayHasKey('lpa', $validatedLpa);
        Assert::assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);
    }

    /**
     * @Given /^The activateBy TTL is removed from the record in the DB$/
     */
    public function theActivateByTTLIsRemovedFromTheRecordInTheDB(): void
    {
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
                                'ActorId'    => '700000000001',
                                'UserId'     => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                            ]
                        ),
                    ],
                ]
            )
        );

        // UserLpaActorMap:: activateRecord
        $this->awsFixtures->append(
            function (CommandInterface $cmd, RequestInterface $req) {
                $newID = $cmd->toArray()['ExpressionAttributeValues'][':a']['N'];
                Assert::assertEquals($this->actorLpaId, $newID);

                return new Result(
                    [
                        'Item' => $this->marshalAwsResultData(
                            [
                                'SiriusUid'  => $this->lpaUid,
                                'Added'      => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'         => $this->userLpaActorToken,
                                'ActorId'    => $this->actorLpaId,
                                'UserId'     => $this->userId,
                                'ActivateBy' => (new DateTime())->modify('+1 year')->getTimestamp(),
                            ]
                        ),
                    ]
                );
            }
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
                $this->actorLpaId,
                $this->userDob,
                $this->userId
            );
        } catch (Exception $ex) {
            throw new Exception('Lpa confirmation unsuccessful');
        }

        //Check response is for correct Item ID
        Assert::assertEquals($this->userLpaActorToken, $response);
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see all of the access codes and their details$/
     */
    public function iCanSeeAllOfTheAccessCodesAndTheirDetails(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
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
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode'   => $this->accessCode,
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

        Assert::assertArrayHasKey($this->userLpaActorToken, $lpa);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

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
                            'ActorId'   => $this->actorLpaId,
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

        // ViewerCodeActivity::getStatusesForViewerCodes($code1)
        $this->awsFixtures->append(
            new Result(['Count' => 0])
        );

        // ViewerCodeActivity::getStatusesForViewerCodes($code2)
        $this->awsFixtures->append(
            new Result(['Count' => 0])
        );

        // UserLpaActorMap::get - $code1
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

        // UserLpaActorMap::get - $code2
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

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        $codesWithStatuses = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertCount(2, $codesWithStatuses);

        // codes service adds some stuff
        $code1['Viewed']  = false;
        $code1['ActorId'] = '700000000054';
        $code2['Viewed']  = false;
        $code2['ActorId'] = '700000000054';

        Assert::assertEquals($codesWithStatuses[0], $code1);
        Assert::assertEquals($codesWithStatuses[1], $code2);

        // Loop for Assert::asserting on both the 2 codes returned
        for ($i = 0; $i < 2; $i++) {
            if ($i === 0) {
                Assert::assertEquals(
                    $codesWithStatuses[$i]['Expires'],
                    (new DateTime())->modify($code1Expiry)->format('Y-m-d')
                );
            } else {
                Assert::assertEquals(
                    $codesWithStatuses[$i]['Expires'],
                    (new DateTime())->modify($code2Expiry)->format('Y-m-d')
                );
            }
        }
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA(): void
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

        Assert::assertArrayHasKey($this->userLpaActorToken, $lpa);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId);
        Assert::assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid);

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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        // ViewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertEmpty($codes);
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired(): void
    {
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

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('actor', $lpaData);
        Assert::assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId'   => $this->actorLpaId,
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => '2021-01-05 12:34:56',
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
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Viewed'     => '2022-01-04 12:34:56',
                                'ViewerCode' => $this->accessCode,
                                'ViewedBy'   => $this->organisation,
                            ]
                        ),
                    ],
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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertArrayHasKey('ViewerCode', $accessCodes[0]);
        Assert::assertArrayHasKey('Expires', $accessCodes[0]);
        Assert::assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        Assert::assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($accessCodes[0]['Expires'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA/
     */
    public function iClickToCheckMyAccessCodeThatIsUsedToViewLPA(): void
    {
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

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('actor', $lpaData);
        Assert::assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId'   => $this->actorLpaId,
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => '2022-01-05 12:34:56',
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
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Viewed'     => '2022-01-04 12:34:56',
                                'ViewerCode' => $this->accessCode,
                                'ViewedBy'   => $this->organisation,
                            ]
                        ),
                    ],
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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertArrayHasKey('ViewerCode', $accessCodes[0]);
        Assert::assertArrayHasKey('Expires', $accessCodes[0]);
        Assert::assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        Assert::assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($accessCodes[0]['Expires'], '2022-01-05 12:34:56');
    }

    /**
     * @When /^I check my access codes$/
     */
    public function iClickToCheckMyAccessCodes(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

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

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('actor', $lpaData);
        Assert::assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId'   => $this->actorLpaId,
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => '2022-01-05 12:34:56',
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
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Viewed'     => '2022-01-04 12:34:56',
                                'ViewerCode' => $this->accessCode,
                                'ViewedBy'   => $this->organisation,
                            ]
                        ),
                    ],
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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        // actor id  does not match the userId returned

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertArrayHasKey('ViewerCode', $accessCodes[0]);
        Assert::assertArrayHasKey('Expires', $accessCodes[0]);
        Assert::assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        Assert::assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($accessCodes[0]['Added'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I click to check the access codes$/
     */
    public function iClickToCheckTheAccessCodes(): void
    {
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

        Assert::assertArrayHasKey('date', $lpaData);
        Assert::assertArrayHasKey('actor', $lpaData);
        Assert::assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        Assert::assertEquals($this->actorLpaId, $lpaData['actor']['details']['uId']);

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
                            'ActorId'   => $this->actorLpaId,
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2021-01-05 12:34:56',
                                'Expires'      => '2022-01-05 12:34:56',
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
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Viewed'     => '2022-01-04 12:34:56',
                                'ViewerCode' => $this->accessCode,
                                'ViewedBy'   => $this->organisation,
                            ]
                        ),
                    ],
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
                            'ActorId'   => $this->actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);

        // actor id  does not match the userId returned
        $codesWithStatuses = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        Assert::assertEquals($codesWithStatuses[0]['Organisation'], $this->organisation);
        Assert::assertEquals($codesWithStatuses[0]['SiriusUid'], $this->lpaUid);
        Assert::assertEquals($codesWithStatuses[0]['UserLpaActor'], $this->userLpaActorToken);
        Assert::assertEquals($codesWithStatuses[0]['ViewerCode'], $this->accessCode);
        Assert::assertEquals($codesWithStatuses[0]['ActorId'], $lpaData['actor']['details']['uId']);
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode(): void
    {
        // Expected fixture calls in this step
        $fixtureCount = 3;

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            function (CommandInterface $cmd, RequestInterface $req) use (&$fixtureCount) {
                Assert::assertEquals('GetItem', $cmd->getName());
                $fixtureCount--;

                return new Result(
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
                );
            }
        );

        // ViewerCodes::get
        $this->awsFixtures->append(
            function (CommandInterface $cmd, RequestInterface $req) use (&$fixtureCount) {
                Assert::assertEquals('GetItem', $cmd->getName());
                $fixtureCount--;

                return new Result(
                    [
                        'Item' => $this->marshalAwsResultData(
                            [
                                'SiriusUid'    => $this->lpaUid,
                                'Added'        => '2020-01-05 12:34:56',
                                'Expires'      => '2021-01-05 12:34:56',
                                'Cancelled'    => '2020-01-15',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                            ]
                        ),
                    ]
                );
            }
        );

        // ViewerCodes::cancel
        $this->awsFixtures->append(
            function (CommandInterface $cmd, RequestInterface $req) use (&$fixtureCount) {
                Assert::assertEquals('UpdateItem', $cmd->getName());
                $fixtureCount--;

                return new Result();
            }
        );

        $viewerCodeService = $this->container->get(ViewerCodeService::class);
        $viewerCodeService->cancelCode($this->userLpaActorToken, $this->userId, $this->accessCode);

        Assert::assertEquals(
            0,
            $fixtureCount,
            'Not all expected fixtures used, expected 3 got ' . 3 - $fixtureCount
        );
    }

    /**
     * @Given /^I confirm details shown to me of the found LPA are correct$/
     */
    public function iConfirmDetailsShownToMeOfTheFoundLPAAreCorrect(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'     => (int) $this->lpa->uId,
            'dob'                  => $this->lpa->donor->dob,
            'postcode'             => $this->lpa->donor->addresses[0]->postcode,
            'first_names'          => $this->lpa->donor->firstname,
            'last_name'            => $this->lpa->donor->surname,
            'force_activation_key' => true,
        ];

        //UserLpaActorMap: getAllForUser
        $this->awsFixtures->append(
            new Result([])
        );

        // pact interaction failed so had to use apiFixtures
        $this->apiFixtures
            ->append(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($lpa)
                )
            );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'       => json_decode(json_encode($lpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $lpa->uId,
            'caseSubtype' => $lpa->caseSubtype,
            'donor'       => [
                'uId'         => $lpa->donor->uId,
                'firstname'   => $lpa->donor->firstname,
                'middlenames' => $lpa->donor->middlenames,
                'surname'     => $lpa->donor->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @Given /^I confirm the details I provided are correct$/
     * @Given /^I provide the details from a valid paper document$/
     * @Then /^I am shown the details of an LPA$/
     * @Then /^I am asked for my contact details$/
     * @Then /^I being the donor on the LPA I am not shown the attorney details$/
     * @When /^I confirm details of the found LPA are correct$/
     */
    public function iAmShownTheDetailsOfAnLPA(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I fill in the form and click the cancel button$/
     */
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
    }

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs(): void
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iHaveCreatedAnAccessCode();
    }

    /**
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument(): void
    {
        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->userPostCode          = 'string';
        $this->userFirstname         = 'Ian Deputy';
        $this->userSurname           = 'Deputy';
        $this->lpa->registrationDate = '2019-09-01';
        $this->userDob               = '1975-10-05';
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'));

        $this->oneTimeCode       = 'XYUPHWQRECHV';
        $this->lpaUid            = '700000000054';
        $this->userDob           = '1975-10-05';
        $this->actorLpaId        = '700000000054';
        $this->userId            = '9999999999';
        $this->userLpaActorToken = '111222333444';
    }

    /**
     * @Given /^I have created an access code$/
     */
    public function iHaveCreatedAnAccessCode(): void
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
        $this->iAmGivenAUniqueAccessCode();
    }

    /**
     * @Given /^I have generated an access code for an organisation and can see the details$/
     */
    public function iHaveGeneratedAnAccessCodeForAnOrganisationAndCanSeeTheDetails(): void
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iClickToCheckMyAccessCodes();
        $this->iCanSeeAllOfMyAccessCodesAndTheirDetails();
    }

    /**
     * @Given /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide details from an LPA registered before Sept 2019$/
     */
    public function iProvideDetailsFromAnLPARegisteredBeforeSept2019(): void
    {
        $this->lpa->registrationDate = '2019-08-31';

        $data = [
            'reference_number' => (int) $this->lpaUid,
            'dob'              => $this->userDob,
            'postcode'         => $this->userPostCode,
            'first_names'      => $this->userFirstname,
            'last_name'        => $this->userSurname,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('LPA not eligible due to registration date', $ex->getMessage());
            return;
        }
    }

    /**
     * @When /^I provide details of an LPA that does not exist$/
     */
    public function iProvideDetailsOfAnLPAThatDoesNotExist(): void
    {
        $invalidLpaId = 700000004321;

        $data = [
            'reference_number'     => $invalidLpaId,
            'dob'                  => $this->userDob,
            'postcode'             => $this->userPostCode,
            'first_names'          => $this->userFirstname,
            'last_name'            => $this->userSurname,
            'force_activation_key' => false,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (NotFoundException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            Assert::assertEquals('LPA not found', $ex->getMessage());
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
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $dob,
            'postcode'             => $postcode,
            'first_names'          => $firstnames,
            'last_name'            => $lastname,
            'force_activation_key' => false,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (NotFoundException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            Assert::assertEquals('LPA not found', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('LPA should not have matched data provided');
    }

    /**
     * @When /^I provide details "([^"]*)" "([^"]*)" "([^"]*)" "([^"]*)" that match a valid paper document$/
     */
    public function iProvideDetailsThatMatchAValidPaperDocument($firstnames, $lastname, $postcode, $dob)
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $this->lpa->donor->firstname = 'Rachel';
        $this->lpa->donor->surname   =  'S’anderson';

        $data = [
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $dob,
            'postcode'             => $postcode,
            'first_names'          => $firstnames,
            'last_name'            => $lastname,
            'force_activation_key' => false,
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

        $codeExists          = new stdClass();
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

        $addAccessForAllLpa = $this->container->get(AddAccessForAllLpa::class);
        $lpaMatchResponse   = $addAccessForAllLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'       => json_decode(json_encode($this->lpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $this->lpa->uId,
            'caseSubtype' => $this->lpa->caseSubtype,
            'donor'       => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @When /^I provide the details from a valid paper LPA document$/
     */
    public function iProvideTheDetailsFromAValidPaperLPADocument(): void
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'     => (int) $this->lpa->uId,
            'dob'                  => $this->lpa->donor->dob,
            'postcode'             => $this->lpa->donor->addresses[0]->postcode,
            'first_names'          => $this->lpa->donor->firstname,
            'last_name'            => $this->lpa->donor->surname,
            'force_activation_key' => false,
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

        $codeExists          = new stdClass();
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

        $addOlderLpa      = $this->container->get(AddAccessForAllLpa::class);
        $lpaMatchResponse = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'       => json_decode(json_encode($this->lpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $this->lpa->uId,
            'caseSubtype' => $this->lpa->caseSubtype,
            'donor'       => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $lpaMatchResponse);
    }

    /**
     * @When /^I provide the details from a valid paper document that already has an activation key$/
     */
    public function iProvideTheDetailsFromAValidPaperDocumentThatAlreadyHasAnActivationKey(): void
    {
        $data = [
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $this->userDob,
            'postcode'             => $this->userPostCode,
            'first_names'          => $this->userFirstname,
            'last_name'            => $this->userSurname,
            'force_activation_key' => false,
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
        $createdDate = (new DateTime())->modify('-14 days');

        $activationKeyDueDate = DateTimeImmutable::createFromMutable($createdDate);
        $activationKeyDueDate = $activationKeyDueDate
            ->add(new DateInterval('P10D'))
            ->format('Y-m-d');

        $codeExists->Created = $createdDate->format('Y-m-d');

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $this->lpaUid,
                'actor' => $this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('LPA has an activation key already', $ex->getMessage());
            Assert::assertEquals(
                [
                    'donor' => [
                        'uId'         => $this->lpa->donor->uId,
                        'firstname'   => $this->lpa->donor->firstname,
                        'middlenames' => $this->lpa->donor->middlenames,
                        'surname'     => $this->lpa->donor->surname,
                    ],
                    'caseSubtype'          => $this->lpa->caseSubtype,
                    'activationKeyDueDate' => $activationKeyDueDate,
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
    public function iRequestToAddAnLPAWithValidDetails(): void
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
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob, //'1980-10-10', // donors DOB as details are now of 'Trust Corporation'
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

        /** @var AddLpa $addLpaService */
        $addLpaService = $this->container->get(AddLpa::class);

        $validatedLpa = $addLpaService->validateAddLpaData(
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            $this->userId
        );

        Assert::assertArrayHasKey('actor', $validatedLpa);
        Assert::assertArrayHasKey('lpa', $validatedLpa);
        Assert::assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs(): void
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';
        $actorLpaId         = 700000000054;

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $actorLpaId,
                            'UserId'    => $this->userId,
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
     * @Then /^I am asked for my role on the LPA$/
     */
    public function iRequestToGoBackAndTryAgain(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     * @When /^I request to remove an LPA from my account that is (.*)$/
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

        // LpaService::getLpaById
        $this->apiFixtures->append(
            new Response(
                StatusCodeInterface::STATUS_OK,
                [],
                json_encode(['lpa' => $this->lpa])
            )
        );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, $this->userId);

        if ($status == 'Revoked') {
            Assert::assertEmpty($lpaData);
        } else {
            Assert::assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
            Assert::assertEquals($this->lpa->id, $lpaData['lpa']['id']);
            Assert::assertEquals($this->lpa->status, $lpaData['lpa']['status']);
        }
    }

    /**
     * @Then /^I should be able to click a link to go and create the access codes$/
     */
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes(): void
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
    }

    /**
     * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithStatus(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be taken back to the access code summary page/
     */
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     * @Then /^I am taken to the remove an LPA confirmation page for (.*) lpa/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I want to cancel the access code for an organisation$/
     */
    public function iWantToCancelTheAccessCodeForAnOrganisation(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I want to see the option to cancel the code$/
     */
    public function iWantToSeeTheOptionToCancelTheCode(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^One of the generated access code has expired$/
     */
    public function oneOfTheGeneratedAccessCodeHasExpired(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt(): void
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
    public function theLPAHasNotBeenAdded(): void
    {
        $lpas = $this->lpaService->getAllForUser($this->userId);

        Assert::assertEmpty($lpas);
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound(): void
    {
        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->oneTimeCode, $this->lpaUid, $this->userDob);

        Assert::assertNull($validatedLpa);
    }

    /**
     * @Then /^The LPA is removed$/
     */
    public function theLPAIsRemoved(): void
    {
        $actorLpaId = 700000000054;

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

        // UserLpaActorMap::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'SiriusUid' => $this->lpaUid,
                            'Added'     => (new DateTime())->modify('-3 months')->format('Y-m-d'),
                            'Id'        => $this->userLpaActorToken,
                            'ActorId'   => $actorLpaId,
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
                            'ActorId'   => $actorLpaId,
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
                            'ActorId'   => $actorLpaId,
                            'UserId'    => $this->userId,
                        ]
                    ),
                ]
            )
        );

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

        Assert::assertEquals($this->lpa->uId, $lpaRemoved['uId']);
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded(): void
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
                $this->actorLpaId
            );
        } catch (Exception $ex) {
            throw new Exception('Lpa confirmation unsuccessful');
        }

        Assert::assertNotNull($response);
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound(): void
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
        $this->lpaService  = $this->container->get(LpaService::class);
        $this->deleteLpa   = $this->container->get(RemoveLpa::class);

        $config                       = $this->container->get('config');
        $this->codesApiPactProvider   = parse_url($config['codes_api']['endpoint'], PHP_URL_HOST);
        $this->apiGatewayPactProvider = parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST);
    }

    /**
     * @Given /^The status of the LPA changed from Registered to Suspended$/
     */
    public function theStatusOfTheLPAChangedFromRegisteredToSuspended(): void
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

        Assert::assertEmpty($lpa);
    }

    /**
     * @Then /^I can still see other LPAs in my account$/
     */
    public function iCanStillSeeTheOtherLPAs(): void
    {
        // functionality tested in anLPAGivesAnUnexpectedError
    }

    /**
     * @Given /^An LPA gives an unexpected error$/
     */
    public function anLPAGivesAnUnexpectedError(): void
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
                                'ActorId'   => $this->actorLpaId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => '700000000138',
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
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => '700000000138',
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
            StatusCodeInterface::STATUS_NOT_FOUND
        );

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/700000000138',
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpa = $this->lpaService->getAllForUser($this->userId);

        assertEquals(1, sizeof($lpa));
    }

    /**
     * @When /^The status of the LPA got Revoked$/
     */
    public function theStatusOfTheLpaGotRevoked(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I check my access codes of the status changed LPA$/
     * @When /^I request to give an organisation access to the LPA whose status changed to Revoked$/
     */
    public function iCheckMyAccessCodesOfTheStatusChangedLpa(): void
    {
        $this->lpa->status = 'Revoked';

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

        Assert::assertEmpty($lpaData);
    }

    /**
     * @Given /^I lost the letter containing my activation key$/
     */
    public function iLostTheLetterContainingMyActivationKey(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should have an option to regenerate an activation key for the old LPA I want to add$/
     */
    public function iShouldHaveAnOptionToRegenerateAnActivationKeyForTheOldLPAIWantToAdd(): void
    {
        // Not needed for this context
    }

    /**
     * @When /^I request for a new activation key again$/
     * @When /^I repeat my request for an activation key$/
     */
    public function iRequestForANewActivationKeyAgain(): void
    {
        $data = [
            'reference_number'     => (int) $this->lpa->uId,
            'first_names'          => $this->lpa->donor->firstname . ' ' . $this->lpa->donor->middlenames,
            'last_name'            => $this->lpa->donor->surname,
            'dob'                  => $this->lpa->donor->dob,
            'postcode'             => $this->lpa->donor->addresses[0]->postcode,
            'force_activation_key' => true,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);
        $response = $addOlderLpa->validateRequest($this->userId, $data);

        $expectedResponse = [
            'actor'       => json_decode(json_encode($this->lpa->donor), true),
            'role'        => 'donor',
            'lpa-id'      => $this->lpa->uId,
            'caseSubtype' => $this->lpa->caseSubtype,
            'donor'       => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
        ];

        Assert::assertEquals($expectedResponse, $response);
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already added to my account$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyAddedToMyAccount(): void
    {
        $differentLpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/test_lpa.json'));

        $data = [
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $this->userDob,
            'postcode'             => $this->userPostCode,
            'first_names'          => $this->userFirstname,
            'last_name'            => $this->userSurname,
            'force_activation_key' => false,
        ];

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
                                'ActorId'   => $this->actorLpaId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $differentLpa->uId,
                                'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                                'Id'        => 'abcd-12345-efgh',
                                'ActorId'   => $this->actorLpaId,
                                'UserId'    => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            // LpaService::getByUserLpaActorToken
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
                                'Updated'   => (new DateTime('2020-01-01'))->format(DateTimeInterface::ATOM),
                                'DueBy'     => (new DateTime('2020-01-01'))->format(DateTimeInterface::ATOM),
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

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            $expectedResponse = [
                'donor'                      => [
                    'uId'         => $this->lpa->donor->uId,
                    'firstname'   => $this->lpa->donor->firstname,
                    'middlenames' => $this->lpa->donor->middlenames,
                    'surname'     => $this->lpa->donor->surname,
                ],
                'caseSubtype'                => $this->lpa->caseSubtype,
                'lpaActorToken'              => $this->userLpaActorToken,
                // would normally expect for this to be a string but this integration test intercepts before it's
                // rendered to an error response.
                'activationKeyDueDate'       => new DateTime('2020-01-01'),
                'activationKeyRequestedDate' => new DateTime('2020-01-01'),
            ];
        } else {
            $expectedResponse = [
                'donor'         => [
                    'uId'         => $this->lpa->donor->uId,
                    'firstname'   => $this->lpa->donor->firstname,
                    'middlenames' => $this->lpa->donor->middlenames,
                    'surname'     => $this->lpa->donor->surname,
                ],
                'caseSubtype'   => $this->lpa->caseSubtype,
                'lpaActorToken' => (int)$this->userLpaActorToken,
            ];
        }

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('LPA already added', $ex->getMessage());
            Assert::assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA already added exception should have been thrown');
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already requested an activation key for$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor(): void
    {
        $createdDate = new DateTimeImmutable('-14 days');
        $dueByDate   = (new DateTimeImmutable('+9 days'))->format(DateTimeInterface::ATOM);
        $updatedDate = (new DateTimeImmutable('-1 days'))->format(DateTimeInterface::ATOM);

        $data = [
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $this->userDob,
            'postcode'             => $this->userPostCode,
            'first_names'          => $this->userFirstname,
            'last_name'            => $this->userSurname,
            'force_activation_key' => false,
        ];

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
                                'ActorId'    => $this->actorLpaId,
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
                            'ActorId'    => $this->actorLpaId,
                            'UserId'     => $this->userId,
                            'ActivateBy' => 123456789,
                            'DueBy'      => $dueByDate,
                            'Updated'    => $updatedDate,
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

        $codeExists = new stdClass();
        $codeExists->Created = $createdDate->format('Y-m-d');

        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $this->lpaUid,
                'actor' => $this->actorLpaId,
            ],
            StatusCodeInterface::STATUS_OK,
            $codeExists
        );

        $expectedResponse = [
            'donor'                      => [
                'uId'         => $this->lpa->donor->uId,
                'firstname'   => $this->lpa->donor->firstname,
                'middlenames' => $this->lpa->donor->middlenames,
                'surname'     => $this->lpa->donor->surname,
            ],
            'caseSubtype'                => $this->lpa->caseSubtype,
            'activationKeyDueDate'       => new DateTimeImmutable($dueByDate),
            'activationKeyRequestedDate' => new DateTimeImmutable($updatedDate),
        ];

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (BadRequestException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            Assert::assertEquals('Activation key already requested for LPA', $ex->getMessage());
            Assert::assertEquals($expectedResponse, $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException(
            'Activation key already requested for LPA exception should have been thrown'
        );
    }

    /**
     * @When I provide details of an LPA that is not registered
     */
    public function iProvideDetailsDetailsOfAnLpaThatIsNotRegistered(): void
    {
        $this->lpa->status = 'Pending';

        $data = [
            'reference_number' => (int) $this->lpaUid,
            'dob'              => $this->userDob,
            'postcode'         => $this->userPostCode,
            'first_names'      => $this->userFirstname,
            'last_name'        => $this->userSurname,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        try {
            $addOlderLpa->validateRequest($this->userId, $data);
        } catch (NotFoundException $ex) {
            Assert::assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
            Assert::assertEquals('LPA status invalid', $ex->getMessage());
            return;
        }
    }

    /**
     * @Given /^My LPA was registered \'([^\']*)\' 1st September 2019 and LPA is \'([^\']*)\' as clean$/
     */
    public function myLPAWasRegistered1stSeptember2019AndLPAIsAsClean($regDate, $cleanseStatus)
    {
        if ($cleanseStatus === 'not marked') {
            $this->lpa->lpaIsCleansed = false;
        } else {
            $this->lpa->lpaIsCleansed = true;
        }

        if ($regDate === 'before') {
            $this->lpa->registrationDate = '2019-08-31';
        } else {
            $this->lpa->registrationDate = '2019-09-01';
        }
    }

    /**
     * @When  /^I am told my activation key request has been received$/
     */
    public function iAmToldMyActivationKeyRequestHasBeenReceived(): void
    {
        $data = [
            'queuedForCleansing' => true,
        ];

        // Lpas::requestLetter
        $this->pactPostInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/requestCode',
            [
                'case_uid' => (int)$this->lpaUid,
                'notes'    => 'notes',
            ],
            StatusCodeInterface::STATUS_OK,
            $data
        );

        if ($this->container->get(FeatureEnabled::class)('save_older_lpa_requests')) {
            // Save activation key request in the DB
            $this->awsFixtures->append(new Result([]));
        }

        $olderLpaService = $this->container->get(AccessForAllLpaService::class);

        try {
            $olderLpaService->requestAccessAndCleanseByLetter((string)$this->lpaUid, $this->userId, 'notes');
        } catch (ApiException $exception) {
            throw new Exception('Failed to request access code letter');
        }
    }

    /**
     * @When I confirm the incorrect details of the found LPA and flag is turned :flagStatus
     */
    public function iConfirmDetailsOfTheFoundLPAAreCorrectAndFlagIsTurned($flagStatus)
    {
        $this->lpa->status = 'Registered';
        $data              = [
            'reference_number'     => (int) $this->lpaUid,
            'dob'                  => $this->userDob,
            'postcode'             => 'WRONG',
            'first_names'          => $this->userFirstname,
            'last_name'            => $this->userSurname,
            'force_activation_key' => false,
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

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        if ($flagStatus == 'ON') {
            try {
                $addOlderLpa->validateRequest($this->userId, $data);
            } catch (NotFoundException $ex) {
                Assert::assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $ex->getCode());
                Assert::assertEquals('LPA not found', $ex->getMessage());
                return;
            }
        } else {
            try {
                $addOlderLpa->validateRequest($this->userId, $data);
            } catch (BadRequestException $ex) {
                Assert::assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
                Assert::assertEquals('LPA details do not match', $ex->getMessage());
                return;
            }
        }
    }
}
