<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\Exception\BadRequestException;
use App\Exception\NotFoundException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\AddLpa;
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
    private OlderLpaService $olderLpaService;

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

        $this->olderLpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId);
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
            assertArrayHasKey('lpa', $ex->getAdditionalData());
            assertArrayHasKey('actor', $ex->getAdditionalData());
            assertArrayHasKey('user-lpa-actor-token', $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('LPA already added exception should have been thrown');
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
     * @Given /^I confirm that those details are correct$/
     */
    public function iConfirmThatThoseDetailsAreCorrect()
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

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        try {
            $this->olderLpaService->checkLPAMatchAndGetActorDetails($data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA not eligible due to registration date', $ex->getMessage());
            return;
        }

        throw new ExpectationFailedException('LPA registration date should not have been eligible');
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

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $invalidLpaId,
            StatusCodeInterface::STATUS_NOT_FOUND,
            []
        );

        try {
            $this->olderLpaService->checkLPAMatchAndGetActorDetails($data);
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
        ];

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        try {
            $this->olderLpaService->checkLPAMatchAndGetActorDetails($data);
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
        $data = [
            'reference_number' => $this->lpaUid,
            'dob' => $this->userDob,
            'postcode' => $this->userPostCode,
            'first_names' => $this->userFirstname,
            'last_name' => $this->userSurname,
        ];

        // LpaRepository::get
        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $codeExists = new stdClass();
        $codeExists->Created = null;

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

        $lpaMatchResponse = $this->olderLpaService->checkLPAMatchAndGetActorDetails($data);

        assertEquals($lpaMatchResponse['lpa-id'], $this->lpaUid);
        assertEquals($lpaMatchResponse['actor-id'], $this->actorLpaId);
    }

    /**
     * @When /^I request an activation key again within 14 calendar days$/
     * @When /^I provide the details from a valid paper document that already has an activation key$/
     */
    public function iRequestAnActivationKeyAgainWithin14CalendarDays()
    {
        $data = [
            'reference_number' => $this->lpaUid,
            'dob' => $this->userDob,
            'postcode' => $this->userPostCode,
            'first_names' => $this->userFirstname,
            'last_name' => $this->userSurname,
        ];

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

        try {
            $this->olderLpaService->checkLPAMatchAndGetActorDetails($data);
        } catch (BadRequestException $ex) {
            assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $ex->getCode());
            assertEquals('LPA not eligible as an activation key already exists', $ex->getMessage());
            assertEquals(['activation_key_created' => $createdDate], $ex->getAdditionalData());
            return;
        }

        throw new ExpectationFailedException('Activation key should have already been requested');
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

        $validatedLpa = $addLpaService->validateAddLpaData(
            [
                'actor-code' => $this->oneTimeCode,
                'uid' => $this->lpaUid,
                'dob' => $this->userDob,
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

        // LpaService::getLpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                )
            );

        $lpaData = $this->lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        if($status == "Revoked"){
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
     * @Then /^The LPA is removed/
     */
    public function theLPAIsRemoved()
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

        // ViewerCodes::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => '1',
                                'ViewerCode' => '123ABCD6789',
                                'SiriusUid' => $this->lpaUid,
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

        $this->awsFixtures->append(new Result());

        // viewerCodesRepository::removeActorAssociation
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

        $this->awsFixtures->append(new Result());

        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => '1',
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'ActorId' => $this->actorLpaId,
                                'UserId' => $this->userId,
                            ]
                        ),
                    ],
                ]
            )
        );

        $this->awsFixtures->append(new Result());

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
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

        // UserLpaActorMap::create
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userLpaActorToken,
                                'UserId' => $this->userId,
                                'SiriusUid' => $this->lpaUid,
                                'ActorId' => $this->actorLpaId,
                                'Added' => $now,
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
        $this->olderLpaService = $this->container->get(OlderLpaService::class);
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

        assertEmpty($lpa);
    }

    /**
     * @When /^The status of the LPA got Revoked$/
     */
    public function theStatusOfTheLpaGotRevoked(){
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

        assertEmpty($lpaData);
    }
}
