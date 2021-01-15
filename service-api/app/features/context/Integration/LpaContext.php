<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaService;
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

/**
 * Class LpaContext
 *
 * A place for context steps relating to LPA interactions such as adding, removing etc.
 *
 * @package BehatTest\Context\Integration
 *
 * @property mixed lpa
 * @property string oneTimeCode
 * @property string lpaUid
 * @property string userLpaActorToken
 * @property string userDob
 * @property string actorLpaId
 * @property string userId
 * @property string organisation
 * @property string accessCode
 * @property string userPostCode
 * @property string userSurname
 * @property string userFirstname
 */
class LpaContext extends BaseIntegrationContext
{
    use SetupEnv;
    use UsesPactContextTrait;

    private MockHandler $apiFixtures;
    private AwsMockHandler $awsFixtures;
    private string $codesApiPactProvider;
    private string $apiGatewayPactProvider;

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);

        $config = $this->container->get('config');
        $this->codesApiPactProvider = parse_url($config['codes_api']['endpoint'], PHP_URL_HOST);
        $this->apiGatewayPactProvider = parse_url($config['sirius_api']['endpoint'], PHP_URL_HOST);
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codeData = $viewerCodeService->addCode($this->userLpaActorToken, $this->userId, $this->organisation);

        $codeExpiry = (new DateTime($codeData['expires']))->format('Y-m-d');
        $in30Days = (new DateTime('23:59:59 +30 days', new DateTimeZone('Europe/London')))->format('Y-m-d');

        assertArrayHasKey('code', $codeData);
        assertNotNull($codeData['code']);
        assertEquals($codeExpiry, $in30Days);
        assertEquals($codeData['organisation'], $this->organisation);
    }

    /**
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument()
    {
        $this->userPostCode = 'string';
        $this->userFirstname = 'Ian Deputy';
        $this->userSurname = 'Deputy';

        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not used in this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
                'code' => $this->oneTimeCode
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => ''
            ],
        );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $response = $actorCodeService->validateDetails($this->oneTimeCode, $this->lpaUid, $this->userDob);

        assertNull($response);
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
            'Expires' => $code1Expiry,
            'UserLpaActor' => $this->userLpaActorToken,
            'Organisation' => $this->organisation,
            'ViewerCode' => $this->accessCode,
        ];

        $code2 = [
            'SiriusUid' => $this->lpaUid,
            'Added' => '2020-01-01T00:00:00Z',
            'Expires' => $code2Expiry,
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

        $lpaService = $this->container->get(LpaService::class);
        $lpa = $lpaService->getAllForUser($this->userId);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
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
                assertEquals($codesWithStatuses[$i]['Expires'], $code1Expiry);
            } else {
                assertEquals($codesWithStatuses[$i]['Expires'], $code2Expiry);
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

        $lpaService = $this->container->get(LpaService::class);
        $lpa = $lpaService->getAllForUser($this->userId);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertEmpty($codes);
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);

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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codeData = $viewerCodeService->cancelCode($this->userLpaActorToken, $this->userId, $this->accessCode);

        assertEmpty($codeData);
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
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
                'code' => $this->oneTimeCode
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => ''
            ],
        );
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function tiRequestToAddAnLPAWithValidDetails()
    {
        // The underlying SmartGamma library has a very naive match processor for
        // passed in response values and will assume lpaUid's and actorLpaId's are integers.
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/validate',
            [
                'lpa'  => $this->lpaUid,
                'dob'  => $this->userDob,
                'code' => $this->oneTimeCode
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'actor' => $this->actorLpaId
            ],
        );

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->oneTimeCode, $this->lpaUid, $this->userDob);

        assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);
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
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken, ['user-token' => $this->userId])
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                )
            );

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->lpa->id, $lpaData['lpa']['id']);
        assertEquals($this->lpa->status, $lpaData['lpa']['status']);
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
        $lpaService = $this->container->get(LpaService::class);

        $lpas = $lpaService->getAllForUser($this->userId);

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
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $now = (new DateTime)->format('Y-m-d\TH:i:s.u\Z');
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
                'code' => $this->oneTimeCode
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

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
                                        'ViewedBy' => 'organisation1'
                                    ],
                                    1 => [
                                        'Viewed' => '2020-10-20T23:59:59+00:00',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => 'organisation2'
                                    ],
                                ]
                            ]
                        ),
                    ],
                ]
            )
        );
        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);

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
     * @Given /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA()
    {
        // Not needed for this context
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string)$this->userId);

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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Expires'], '2021-01-05 12:34:56');
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA()
    {
        // Not needed for this context
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
                            'SiriusUid' => '700000055554',
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
                                'Id'            => '1',
                                'ViewerCode'    => '123ABCD6789',
                                'SiriusUid'     => '700000055554',
                                'Added'         => '2021-01-01 00:00:00',
                                'Expires'       => '2021-02-01 00:00:00',
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
                                'UserId' => $this->userId
                            ]
                        ),
                    ],
                ]
            )
        );

        $this->awsFixtures->append(new Result());

        $lpaService = $this->container->get(\App\Service\Lpa\LpaService::class);
        $lpaRemoveResponse = $lpaService->removeLpaFromUserLpaActorMap($this->userId, $this->userLpaActorToken);

        assertEmpty($lpaRemoveResponse);
    }

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
                'case_uid' => (int) $this->lpaUid,
                'actor_uid' => (int) $this->actorLpaId
            ],
            StatusCodeInterface::STATUS_NO_CONTENT
        );

        $lpaService = $this->container->get(\App\Service\Lpa\LpaService::class);

        $lpaService->requestAccessByLetter($this->lpaUid, $this->actorLpaId);
    }

    /**
     * @Given /^I am on the add an older LPA page$/
     */
    public function iAmOnTheAddAnOlderLPAPage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I confirm that those details are correct$/
     */
    public function iConfirmThatThoseDetailsAreCorrect()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide the details from a valid paper document$/
     */
    public function iProvideTheDetailsFromAValidPaperDocument()
    {
        $data = [
            'reference_number'  => '700000000054',
            'dob'               => '1975-10-05',
            'postcode'          => 'string',
            'first_names'       => 'Ian Deputy',
            'last_name'         => 'Deputy',
        ];

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_OK,
            $this->lpa
        );

        $lpaService = $this->container->get(LpaService::class);

        $lpaMatchResponse = $lpaService->checkLPAMatch($data);
        assertEquals($lpaMatchResponse['lpa-id'], $this->lpaUid);
    }

    /**
     * @Then /^The old LPA is not found$/
     */
    public function theOldLpaIsNotFound()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am shown the LPA not found message$/
     */
    public function iAmShownTheLpaNotFoundMessage()
    {
        // Not needed for this context
    }

    /**
     * @When /^I provide the details from a valid paper document$/
     */
    public function iProvideTheDetailsFromThePaperDocumentButDoesNotExist()
    {
        $data = [
            'reference_number'  => '700000000055',
            'dob'               => '1975-10-05',
            'postcode'          => 'string',
            'first_names'       => 'Ian Deputy',
            'last_name'         => 'Deputy',
        ];

        $this->pactGetInteraction(
            $this->apiGatewayPactProvider,
            '/v1/use-an-lpa/lpas/' . $this->lpaUid,
            StatusCodeInterface::STATUS_NOT_FOUND,
            []
        );

        $lpaService = $this->container->get(LpaService::class);

        $lpaMatchResponse = $lpaService->checkLPAMatch($data);
        assertNull($lpaMatchResponse);
    }

    /**
     * @Given /^I already have a valid activation key for my LPA$/
     */
    public function iAlreadyHaveAValidActivationKeyForMyLPA()
    {
        $this->pactPostInteraction(
            $this->codesApiPactProvider,
            '/v1/exists',
            [
                'lpa'   => $this->lpaUid,
                'actor' => $this->actorLpaId
            ],
            StatusCodeInterface::STATUS_OK,
            [
                'Created' => '2020-09-10'
            ],
        );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $response = $actorCodeService->hasActivationCode($this->lpaUid, $this->actorLpaId);

        assertTrue($response);
    }

}
