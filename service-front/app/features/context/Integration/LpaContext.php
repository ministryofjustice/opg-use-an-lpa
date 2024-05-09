<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use BehatTest\Context\ActorContextTrait;
use BehatTest\Context\ContextUtilities;
use Common\Entity\CaseActor;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\AccessForAllApiResult;
use Common\Service\Lpa\AddAccessForAllLpa;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResult;
use Common\Service\Lpa\CleanseLpa;
use Common\Service\Lpa\InstAndPrefImagesService;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\RemoveLpa;
use Common\Service\Lpa\Response\AccessForAllResult;
use Common\Service\Lpa\Response\ActivationKeyExists;
use Common\Service\Lpa\Response\LpaAlreadyAdded;
use Common\Service\Lpa\Response\LpaMatch;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\Notify\NotifyService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\Assert;

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 *
 * @property array lpa
 * @property string lpaJson
 * @property string lpaData
 * @property string activation_key
 * @property string referenceNo
 * @property string userDob
 * @property string userIdentity
 * @property string actorLpaToken
 * @property int actorId
 * @property string organisation
 * @property string accessCode
 * @property string userPostCode
 * @property string userFirstname
 * @property string userMiddlenames
 * @property string userSurname
 * @property string codeCreatedDate
 * @psalm-ignore UndefinedThisPropertyFetch
 */
class LpaContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    private const ADD_LPA_VALIDATE                      = 'AddLpa::validate';
    private const ADD_LPA_CONFIRM                       = 'AddLpa::confirm';
    private const LPA_SERVICE_GET_LPA_BY_ID             = 'LpaService::getLpaById';
    private const VIEWER_CODE_SERVICE_CREATE_SHARE_CODE = 'ViewerCodeService::createShareCode';
    private const VIEWER_CODE_SERVICE_GET_SHARE_CODES   = 'ViewerCodeService::getShareCodes';
    private const VIEWER_CODE_SERVICE_CANCEL_SHARE_CODE = 'ViewerCodeService::cancelShareCode';
    private const ADD_OLDER_LPA_VALIDATE                = 'AddOlderLpa::validate';
    private const ADD_OLDER_LPA_CONFIRM                 = 'AddOlderLpa::confirm';
    private const CLEANSE_LPA_CLEANSE                   = 'CleanseLpa::cleanse';
    private const LPA_SERVICE_GET_LPAS                  = 'LpaService::getLpas';
    private const REMOVE_LPA_INVOKE                     = 'RemoveLpa::__invoke';
    private const INPSERVICE_GET_BY_ID                  = 'InstAndPrefImagesService::getImagesById';

    private LpaFactory $lpaFactory;
    private LpaService $lpaService;
    private ViewerCodeService $viewerCodeService;
    private NotifyService $notifyService;
    private InstAndPrefImagesService $instAndPrefImagesService;

    /**
     * @Given /^I am told that I have already requested an activation key for this LPA$/
     */
    public function iAmToldThatIHaveAlreadyRequestedAnActivationKeyForThisLPA()
    {
        // API call for requesting activation code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'Activation key already requested for LPA',
                        'data'    => [
                            'donor'       => [
                                'uId'         => $this->lpa['donor']['uId'],
                                'firstname'   => $this->lpa['donor']['firstname'],
                                'middlenames' => $this->lpa['donor']['middlenames'],
                                'surname'     => $this->lpa['donor']['surname'],
                            ],
                            'caseSubtype' => $this->lpa['caseSubtype'],
                        ],
                    ]
                )
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode
        );

        $donor = new CaseActor();
        $donor->setUId($this->lpa['donor']['uId']);
        $donor->setFirstname($this->lpa['donor']['firstname']);
        $donor->setMiddlenames($this->lpa['donor']['middlenames']);
        $donor->setSurname($this->lpa['donor']['surname']);

        $keyExistsDTO = new ActivationKeyExists();
        $keyExistsDTO->setDonor($donor);
        $keyExistsDTO->setCaseSubtype($this->lpa['caseSubtype']);

        $response = new AccessForAllApiResult(
            AccessForAllResult::KEY_ALREADY_REQUESTED,
            $keyExistsDTO
        );

        Assert::assertEquals($response, $result);
    }

    /**
     * @Given /^I cannot see my LPA on the dashboard$/
     */
    public function iCannotSeeMyLPAOnTheDashboard()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I can see a flash message confirming that my LPA has been removed$/
     */
    public function iCanSeeAFlashMessageConfirmingThatMyLPAHasBeenRemoved()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The LPA is removed/
     */
    public function theLPAIsRemoved()
    {
        $this->apiFixtures->reset();
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['lpa' => $this->lpa]),
                self::REMOVE_LPA_INVOKE
            )
        );

        $removeLpa = $this->container->get(RemoveLpa::class);
        $result    = $removeLpa($this->userIdentity, $this->actorLpaToken);

        Assert::assertArrayHasKey('lpa', $result);
        Assert::assertEquals($this->lpa['uId'], $result['lpa']->getUId());
    }

    /**
     * @Given /^My active codes are cancelled$/
     */
    public function myActiveCodesAreCancelled()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I confirm that I want to remove the LPA from my account$/
     * @Then /^I am taken to the remove an LPA confirmation page for Revoked lpa$/
     * @Then /^I am taken to the remove an LPA confirmation page for Cancelled lpa$/
     * @Then /^I am taken to the remove an LPA confirmation page for Registered lpa$/
     */
    public function iConfirmThatIWantToRemoveTheLPAFromMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to remove an LPA from my account that is (.*)$/
     */
    public function iRequestToRemoveAnLPAFromMyAccountThatIs($status)
    {
        if ($status === 'Registered' or  $status === 'Cancelled') {
            $this->lpa['status'] = $status;

            // API call for get LpaById (when give organisation access is clicked)
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date'                 => 'date',
                            'lpa'                  => [],
                            'actor'                => $this->lpaData['actor'],
                        ]
                    ),
                )
            );
        } else {
            // API call for get LpaById (when give organisation access is clicked)
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date'                 => 'date',
                            'lpa'                  => [],
                            'actor'                => null,
                        ]
                    ),
                )
            );
        }
    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     * @When /^I request for a new activation key again$/
     * @Then  /^I am told my activation key is being sent$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NO_CONTENT,
                json_encode([]),
                self::ADD_OLDER_LPA_CONFIRM
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->confirm(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
            false
        );

        $response = new AccessForAllApiResult(AccessForAllResult::SUCCESS, []);
        Assert::assertEquals($response, $result);
    }

    /**
     * @Given /^I already have a valid activation key for my LPA$/
     */
    public function iAlreadyHaveAValidActivationKeyForMyLPA()
    {
        $this->activation_key  = 'XYUPHWQRECHV';
        $this->codeCreatedDate = (new DateTime())->modify('-15 days')->format('Y-m-d');
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $codeData = $this->viewerCodeService->createShareCode(
            $this->userIdentity,
            $this->actorLpaToken,
            $this->organisation
        );

        Assert::assertNotEmpty($lpa);
        Assert::assertEquals($this->accessCode, $codeData['code']);
        Assert::assertEquals($this->organisation, $codeData['organisation']);
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     * @Then /^I am informed that an LPA could not be found$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        $allowedErrorMessages = [
            AccessForAllResult::DOES_NOT_MATCH,
            AccessForAllResult::NOT_FOUND,
            AccessForAllResult::NOT_ELIGIBLE,
        ];

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);
        $result      = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode
        );

        Assert::assertTrue(in_array($result->getResponse(), $allowedErrorMessages));
    }

    /**
     * @Given /^I am on the add an LPA page$/
     * @Given /^I provide the additional details asked$/
     * @Given /^I am asked to consent and confirm my details$/
     * @Given /^I have provided my current address$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not needed for this context
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
        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA not eligible due to registration date',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode
        );

        $response = new AccessForAllApiResult(AccessForAllResult::NOT_ELIGIBLE, []);

        Assert::assertEquals($response, $result);
    }

    /**
     * @Given /^I can see a flash message for the added LPA$/
     */
    public function iCanSeeAFlashMessageForTheAddedLPA()
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
     * @Then /^I can see the relevant (.*) and (.*) of my access codes and their details$/
     */
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle)
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        $code1 = [
            'SiriusUid'    => $this->referenceNo,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->actorLpaToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => $code1Expiry,
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        $code2 = [
            'SiriusUid'    => $this->referenceNo,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->actorLpaToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => $code2Expiry,
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->actorLpaToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => $code1,
                        1 => $code2,
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $lpa = $this->lpaService->getLpas($this->userIdentity);

        $lpaObject = $this->lpaFactory->createLpaFromData($this->lpa);

        Assert::assertEquals($lpaObject, $lpa[$this->actorLpaToken]['lpa']);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, true);

        Assert::assertEquals($shareCodes[0], $code1);
        Assert::assertEquals($shareCodes[1], $code2);
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([$this->actorLpaToken => $this->lpaData]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        //API call for getting each LPAs share codes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $lpa = $this->lpaService->getLpas($this->userIdentity);

        $lpaObject = $this->lpaFactory->createLpaFromData($this->lpa);

        Assert::assertEquals($lpaObject, $lpa[$this->actorLpaToken]['lpa']);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, true);

        Assert::assertEquals($shareCodes['activeCodeCount'], 0);
    }

    /**
     * @Then /^I can see the code has not been used to view the LPA$/
     */
    public function iCanSeeTheCodeHasNotBeenUsedToViewTheLPA()
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
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        // Not needed for this context
    }

    /**
     * @When /^I check my access codes$/
     */
    public function iCheckMyAccessCodes()
    {

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
            )
        );

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        Assert::assertEmpty($shareCodes);
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2020-01-02T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);


        Assert::assertNotEmpty($lpa);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        Assert::assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        Assert::assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        Assert::assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        Assert::assertEquals(false, $shareCodes[0]['Viewed']);
        //check if the code expiry date is in the past
        Assert::assertGreaterThan(
            strtotime($shareCodes[0]['Expires']),
            strtotime((new DateTime('now'))->format('Y-m-d'))
        );
        Assert::assertGreaterThan(strtotime($shareCodes[0]['Added']), strtotime($shareCodes[0]['Expires']));
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA/
     */
    public function iClickToCheckMyAccessCodeThatIsUsedToViewLPA()
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => [
                                0 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => $this->organisation,
                                ],
                                1 => [
                                    'Viewed'     => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy'   => 'Another Organisation',
                                ],
                            ],
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );
        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        Assert::assertNotEmpty($lpa['lpa']);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        Assert::assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        Assert::assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        Assert::assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);

        Assert::assertNotEmpty($shareCodes[0]['Viewed']);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['Viewed'][0]['ViewerCode']);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['Viewed'][1]['ViewerCode']);
        Assert::assertEquals($this->organisation, $shareCodes[0]['Viewed'][0]['ViewedBy']);
        Assert::assertEquals('Another Organisation', $shareCodes[0]['Viewed'][1]['ViewedBy']);
    }

    /**
     * @When /^I click to check my access codes$/
     */
    public function iClickToCheckMyAccessCodes()
    {

        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );


        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        Assert::assertNotEmpty($lpa['lpa']);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        Assert::assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        Assert::assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        Assert::assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        Assert::assertEquals(false, $shareCodes[0]['Viewed']);
    }

    /**
     * @When /^I click to check my active and inactive codes$/
     */
    public function iClickToCheckMyActiveAndInactiveCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                        1 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2020-02-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => 'ABC321ABCXYZ',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        Assert::assertNotEmpty($lpa);
        Assert::assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        Assert::assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        Assert::assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        Assert::assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        Assert::assertEquals(false, $shareCodes[0]['Viewed']);

        Assert::assertEquals('ABC321ABCXYZ', $shareCodes[1]['ViewerCode']);
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        // API call for cancelShareCode in CancelCodeHandler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::VIEWER_CODE_SERVICE_CANCEL_SHARE_CODE
            )
        );

        $this->viewerCodeService->cancelShareCode(
            $this->userIdentity,
            $this->actorLpaToken,
            $this->accessCode
        );

        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        // API call for getShareCodes in CheckAccessCodesHandler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'Cancelled'    => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $this->userIdentity,
            $this->actorLpaToken,
            false
        );

        Assert::assertEquals($shareCodes[0]['Organisation'], $this->organisation);
        Assert::assertEquals($shareCodes[0]['Cancelled'], '2021-01-01T23:59:59+00:00');
    }

    /**
     * @When /^I confirm the details I provided are correct$/
     * @Then /^I confirm details shown to me of the found LPA are correct$/
     * @Then /^I confirm details of the found LPA are correct$/
     */
    public function iConfirmTheDetailsIProvidedAreCorrect()
    {
        // Not needed for this context
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveAddedAnLPAToMyAccount()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();
        $this->iAmOnTheAddAnLPAPage();
        $this->iRequestToAddAnLPAWithValidDetailsUsing($this->activation_key, $this->activation_key);
        $this->theCorrectLPAIsFoundAndICanConfirmToAddIt();
        $this->theLPAIsSuccessfullyAdded();
    }

    /**
     * @Given /^I have been given access to use an LPA via a paper document$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaAPaperDocument()
    {
        $this->userPostCode    = 'string';
        $this->userFirstname   = 'Ian Deputy';
        $this->userMiddlenames = '';
        $this->userSurname     = 'Deputy';

        // sets up the normal properties needed for an lpa
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();

        $this->activation_key = ''; // reset this to blank as we won't have one normally
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpaJson = file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json');
        $this->lpa     = json_decode($this->lpaJson, true);

        $this->activation_key = 'XYUPHWQRECHV';
        $this->referenceNo    = '700000000138';
        $this->userDob        = '1975-10-05';
        $this->actorLpaToken  = '24680';
        $this->actorId        = 0;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->actorLpaToken,
            'date'                 => 'today',
            'actor'                => [
                'type'    => 'primary-attorney',
                'details' => [
                    'addresses'    => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary',
                        ],
                    ],
                    'companyName'  => null,
                    'dob'          => '1975-10-05',
                    'email'        => 'test@test.com',
                    'firstname'    => 'Ian',
                    'id'           => 0,
                    'middlenames'  => null,
                    'salutation'   => 'Mr',
                    'surname'      => 'Deputy',
                    'systemStatus' => true,
                    'uId'          => '700000000054',
                ],
            ],
            'lpa'                  => $this->lpa,
        ];
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
     * @When /^I have shared the access code with organisations to view my LPA$/
     * @Given /^I have shared the access code with organisations and they have viewed my LPA$/
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
        // Not needed for this context
    }

    /**
     * @When /^I provide details that do not match a valid paper document$/
     */
    public function iProvideDetailsThatDoNotMatchAValidPaperDocument()
    {
        // Setup fixture for success response
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'LPA details do not match',
                        'details' => 'LPA details do not match',
                        'data'    => [],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @Given /^My LPA has been found but my details did not match$/
     */
    public function myLPAHasBeenFoundButMyDetailsDidNotMatch()
    {
        // not used
    }

    /**
     * @When /^I provide an LPA number that does not exist$/
     */
    public function iProvideAnLPANumberThatDoesNotExist()
    {
        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                '',
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @Then /^I am informed that an LPA could not be found with this reference number$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithThisReferenceNumber()
    {
        $allowedErrorMessages = [AccessForAllResult::NOT_FOUND, AccessForAllResult::NOT_ELIGIBLE];

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);
        $result      = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode
        );

        Assert::assertTrue(in_array($result->getResponse(), $allowedErrorMessages));
    }

    /**
     * @When /^I provide the details from a valid paper document$/
     */
    public function iProvideTheDetailsFromAValidPaperDocument()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to add an LPA with valid details using (.*) which matches (.*)$/
     */
    public function iRequestToAddAnLPAWithValidDetailsUsing(string $code, string $storedCode)
    {
        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->lpaData),
                self::ADD_LPA_VALIDATE
            )
        );

        $addLpa  = $this->container->get(AddLpa::class);
        $lpaData = $addLpa->validate(
            $this->userIdentity,
            $storedCode,
            $this->referenceNo,
            $this->userDob
        );

        Assert::assertInstanceOf(AddLpaApiResult::class, $lpaData);
        Assert::assertEquals(AddLpaApiResult::ADD_LPA_FOUND, $lpaData->getResponse());
        Assert::assertEquals(($lpaData->getData()['lpa'])->getUId(), $this->lpa['uId']);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = 'TestOrg';
        $this->accessCode   = 'XYZ321ABC987';

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        // API call to make code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'code'         => $this->accessCode,
                        'expires'      => '2021-03-07T23:59:59+00:00',
                        'organisation' => $this->organisation,
                    ]
                ),
                self::VIEWER_CODE_SERVICE_CREATE_SHARE_CODE
            )
        );
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->lpa['status'] = $status;

        if ($status === 'Revoked') {
            // API call for getting the LPA by id
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date'                 => 'date',
                            'lpa'                  => [],
                            'actor'                => $this->lpaData['actor'],
                        ]
                    )
                )
            );
        } else {
            // API call for getting the LPA by id
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date'                 => 'date',
                            'lpa'                  => $this->lpa,
                            'actor'                => $this->lpaData['actor'],
                        ]
                    ),
                    self::LPA_SERVICE_GET_LPA_BY_ID
                )
            );



            // InstAndPrefImagesService::getImagesById
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'uId'        => (int) $this->lpa['uId'],
                            'status'     => 'COLLECTION_COMPLETE',
                            'signedUrls' => [],
                        ]
                    ),
                    self::INPSERVICE_GET_BY_ID
                )
            );
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
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithStatus()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the expired viewer code with expired status $/
     */
    public function iShouldBeShownTheDetailsOfTheExpiredViewerCodeWithExpiredStatus()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be taken back to the access code summary page/
     */
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage()
    {
        // API call for get LpaById
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ]
                ),
                self::LPA_SERVICE_GET_LPA_BY_ID
            )
        );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        Assert::assertNotNull($lpa);

        //API call for getShareCodes
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        0 => [
                            'SiriusUid'    => $this->lpa['uId'],
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Expires'      => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode'   => $this->accessCode,
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ],
                    ]
                ),
                self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
            )
        );

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $this->userIdentity,
            $this->actorLpaToken,
            false
        );

        Assert::assertEquals($shareCodes[0]['Organisation'], $this->organisation);
    }

    /**
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should not see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldNotSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
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
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The full LPA is displayed with the correct (.*)$/
     */
    public function theFullLPAIsDisplayedWithTheCorrect($message)
    {
        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        Assert::assertNotNull($lpa->lpa);
        Assert::assertNotNull($lpa->actor);

        $images = $this->instAndPrefImagesService->getImagesById($this->userIdentity, $this->actorLpaToken);
    }

    /**
     * @Given /^The LPA has not been added$/
     */
    public function theLPAHasNotBeenAdded()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        // API call for checking LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                json_encode(
                    [
                        'title'   => 'Not found',
                        'details' => 'Code validation failed',
                        'data'    => [],
                    ]
                ),
                self::ADD_LPA_VALIDATE
            )
        );

        $addLpa = $this->container->get(AddLpa::class);

        $response = $addLpa->validate(
            $this->userIdentity,
            $this->activation_key,
            $this->referenceNo,
            $this->userDob
        );

        Assert::assertEquals(AddLpaApiResult::ADD_LPA_NOT_FOUND, $response->getResponse());
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->actorLpaToken = '24680';
        $this->actorId       = 9;

        // API call for adding an LPA
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CREATED,
                json_encode(['user-lpa-actor-token' => $this->userIdentity]),
                self::ADD_LPA_CONFIRM
            )
        );

        $addLpa   = $this->container->get(AddLpa::class);
        $response = $addLpa->confirm(
            $this->userIdentity,
            $this->activation_key,
            $this->referenceNo,
            $this->userDob
        );



        Assert::assertEquals(AddLpaApiResult::ADD_LPA_SUCCESS, $response->getResponse());
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures              = $this->container->get(MockHandler::class);
        $this->lpaService               = $this->container->get(LpaService::class);
        $this->lpaFactory               = $this->container->get(LpaFactory::class);
        $this->viewerCodeService        = $this->container->get(ViewerCodeService::class);
        $this->instAndPrefImagesService = $this->container->get(InstAndPrefImagesService::class);

        // The user is signed in for all actions of this context
        $this->userIdentity = '123';
    }

    /**
     * @Then /^I receive an email confirming activation key request$/
     * @Then /^I am told a new activation key is posted to the provided postcode$/
     */
    public function iReceiveAnEmailConfirmingActivationKeyRequest()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that I have an activation key for this LPA and where to find it$/
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt()
    {
        $createdDate = (new DateTime())->modify('-14 days');

        // API call for requesting activation code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA has an activation key already',
                        'data'    => [
                            'donor'                => [
                                'uId'         => $this->lpa['donor']['uId'],
                                'firstname'   => $this->lpa['donor']['firstname'],
                                'middlenames' => $this->lpa['donor']['middlenames'],
                                'surname'     => $this->lpa['donor']['surname'],
                            ],
                            'caseSubtype'          => $this->lpa['caseSubtype'],
                            'activationKeyDueDate' => $createdDate->format('c'),
                        ],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode
        );

        $donor = new CaseActor();
        $donor->setUId($this->lpa['donor']['uId']);
        $donor->setFirstname($this->lpa['donor']['firstname']);
        $donor->setMiddlenames($this->lpa['donor']['middlenames']);
        $donor->setSurname($this->lpa['donor']['surname']);

        $keyExistsDTO = new ActivationKeyExists();
        $keyExistsDTO->setDonor($donor);
        $keyExistsDTO->setCaseSubtype($this->lpa['caseSubtype']);
        $keyExistsDTO->setDueDate($createdDate->format('c'));

        $response = new AccessForAllApiResult(
            AccessForAllResult::HAS_ACTIVATION_KEY,
            $keyExistsDTO
        );
        Assert::assertEquals($response, $result);
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already added to my account$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyAddedToMyAccount()
    {
        // API call for requesting activation code
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA already added',
                        'data'    => [
                            'donor'         => [
                                'uId'         => $this->lpa['donor']['uId'],
                                'firstname'   => $this->lpa['donor']['firstname'],
                                'middlenames' => $this->lpa['donor']['middlenames'],
                                'surname'     => $this->lpa['donor']['surname'],
                            ],
                            'caseSubtype'   => $this->lpa['caseSubtype'],
                            'lpaActorToken' => $this->actorLpaToken,
                        ],
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
            false
        );

        $donor = new CaseActor();
        $donor->setUId($this->lpa['donor']['uId']);
        $donor->setFirstname($this->lpa['donor']['firstname']);
        $donor->setMiddlenames($this->lpa['donor']['middlenames']);
        $donor->setSurname($this->lpa['donor']['surname']);

        $alreadyAddedDTO = new LpaAlreadyAdded();
        $alreadyAddedDTO->setDonor($donor);
        $alreadyAddedDTO->setCaseSubtype($this->lpa['caseSubtype']);
        $alreadyAddedDTO->setLpaActorToken($this->actorLpaToken);

        $response = new AccessForAllApiResult(
            AccessForAllResult::LPA_ALREADY_ADDED,
            $alreadyAddedDTO
        );

        Assert::assertEquals($response, $result);
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        // API call for checking add LPA data
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_BAD_REQUEST,
                json_encode(
                    [
                        'title'   => 'Bad Request',
                        'details' => 'LPA already added',
                        'data'    => [
                            'donor'         => [
                                'uId'         => $this->lpa['donor']['uId'],
                                'firstname'   => $this->lpa['donor']['firstname'],
                                'middlenames' => $this->lpa['donor']['middlenames'],
                                'surname'     => $this->lpa['donor']['surname'],
                            ],
                            'caseSubtype'   => $this->lpa['caseSubtype'],
                            'lpaActorToken' => $this->actorLpaToken,
                        ],
                    ]
                ),
                self::ADD_LPA_VALIDATE
            )
        );

        $donor = new CaseActor();
        $donor->setUId($this->lpa['donor']['uId']);
        $donor->setFirstname($this->lpa['donor']['firstname']);
        $donor->setMiddlenames($this->lpa['donor']['middlenames']);
        $donor->setSurname($this->lpa['donor']['surname']);

        $addLpa          = $this->container->get(AddLpa::class);
        $alreadyAddedDTO = new LpaAlreadyAdded();
        $alreadyAddedDTO->setDonor($donor);
        $alreadyAddedDTO->setCaseSubtype($this->lpa['caseSubtype']);
        $alreadyAddedDTO->setLpaActorToken($this->actorLpaToken);

        $response = $addLpa->validate(
            $this->userIdentity,
            $this->activation_key,
            $this->referenceNo,
            $this->userDob
        );

        Assert::assertEquals(AddLpaApiResult::ADD_LPA_ALREADY_ADDED, $response->getResponse());
        Assert::assertEquals($alreadyAddedDTO, $response->getData());
    }

    /**
     * @Then /^I am shown the details of an LPA$/
     * @Given /^I am on the check LPA details page$/
     */
    public function iAmShownTheDetailsOfAnLPA()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'donor'       => [
                            'uId'         => $this->lpa['donor']['uId'],
                            'firstname'   => $this->lpa['donor']['firstname'],
                            'middlenames' => $this->lpa['donor']['middlenames'],
                            'surname'     => $this->lpa['donor']['surname'],
                        ],
                        'caseSubtype' => $this->lpa['caseSubtype'],
                        'role'        => 'donor',
                    ]
                ),
                self::ADD_OLDER_LPA_VALIDATE
            )
        );

        $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

        $result = $addOlderLpa->validate(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
            false
        );

        $donor = new CaseActor();
        $donor->setUId($this->lpa['donor']['uId']);
        $donor->setFirstname($this->lpa['donor']['firstname']);
        $donor->setMiddlenames($this->lpa['donor']['middlenames']);
        $donor->setSurname($this->lpa['donor']['surname']);

        $foundMatchLpaDTO = new LpaMatch();
        $foundMatchLpaDTO->setDonor($donor);
        $foundMatchLpaDTO->setCaseSubtype($this->lpa['caseSubtype']);

        $response = new AccessForAllApiResult(
            AccessForAllResult::FOUND,
            $foundMatchLpaDTO
        );
        Assert::assertEquals($response, $result);
    }

    /**
     * @When /^I provide the details from a valid paper LPA which I have already requested an activation key for$/
     */
    public function iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor()
    {
        // Not needed for this context
    }

    /**
     * @When I provide details of an LPA that is not registered
     */
    public function iProvideDetailsDetailsOfAnLpaThatIsNotRegistered()
    {
        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                '',
                self::ADD_OLDER_LPA_VALIDATE
            )
        );
    }

    /**
     * @Given  /^I have confirmed the details of an older paper LPA after requesting access previously$/
     */
    public function iHaveConfirmedTheDetailsOfAnOlderLpaAfterRequestingAccessPreviously()
    {
        $this->iAmOnTheAddAnOlderLPAPage();
        $this->iProvideTheDetailsFromAValidPaperLPAWhichIHaveAlreadyRequestedAnActivationKeyFor();
        $this->iConfirmTheDetailsIProvidedAreCorrect();
        $this->iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt();
    }

    /**
     * @Given /^My LPA was registered \'([^\']*)\' 1st September 2019 and LPA is \'([^\']*)\' as clean$/
     */
    public function myLPAWasRegistered1stSeptemberAndLPAIsAsClean($regDate, $cleanseStatus)
    {
        if ($cleanseStatus === 'not marked') {
            $this->lpa['lpaIsCleansed'] = false;
        } else {
            $this->lpa['lpaIsCleansed'] = true;
        }

        if ($regDate === 'before') {
            $this->lpa['registrationDate'] = '2019-08-31';
        } else {
            $this->lpa['registrationDate'] = '2019-09-01';
        }
    }

    /**
     * @Given /^I am on the Check we've found the right LPA page$/
     * @Given /^I have provided valid details that match the Lpa$/
     * @Then /^I should expect it within 2 weeks time$/
     * @Then /^I will receive an email confirming this information$/
     * @Then /^I am told my activation key request has been received$/
     * @Then /^I should expect it within 4 weeks time$/
     * @Given /^I provide my contact details$/
     */
    public function iAmOnTheCheckWeHaveFoundTheRightLpaPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am asked for my contact details$/
     */
    public function iAmAskedForMyContactDetails()
    {
        $earliestRegDate = '2019-09-01';

        if (!$this->lpa['lpaIsCleansed'] && $this->lpa['registrationDate'] < $earliestRegDate) {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    json_encode(
                        [
                            'title'   => 'Bad request',
                            'details' => 'LPA needs cleansing',
                            'data'    => [
                                'actor_id' => $this->actorId,
                            ],
                        ]
                    ),
                    self::ADD_OLDER_LPA_CONFIRM
                )
            );

            $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

            $result = $addOlderLpa->confirm(
                $this->userIdentity,
                intval($this->referenceNo),
                $this->userFirstname,
                $this->userSurname,
                DateTime::createFromFormat('Y-m-d', $this->userDob),
                $this->userPostCode,
                true
            );

            Assert::assertEquals(AccessForAllResult::OLDER_LPA_NEEDS_CLEANSING, $result->getResponse());
        } else {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_NO_CONTENT,
                    json_encode([])
                )
            );

            // API call for Notify
            $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

            $addOlderLpa = $this->container->get(AddAccessForAllLpa::class);

            $result = $addOlderLpa->confirm(
                $this->userIdentity,
                intval($this->referenceNo),
                $this->userFirstname,
                $this->userSurname,
                DateTime::createFromFormat('Y-m-d', $this->userDob),
                $this->userPostCode,
                true
            );

            $response = new AccessForAllApiResult(AccessForAllResult::SUCCESS, []);
            Assert::assertEquals($response, $result);
        }
    }

    /**
     * @Given /^I confirm that the data is correct and click the confirm and submit button$/
     */
    public function iConfirmThatTheDataIsCorrectAndClickTheConfirmAndSubmitButton()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NO_CONTENT,
                json_encode([]),
                self::CLEANSE_LPA_CLEANSE
            )
        );

        $cleanseLpa = $this->container->get(CleanseLpa::class);

        $result = $cleanseLpa->cleanse(
            $this->userIdentity,
            intval($this->referenceNo),
            'Notes',
            null
        );

        $response = new AccessForAllApiResult(AccessForAllResult::SUCCESS, []);
        Assert::assertEquals($response, $result);
    }

    /**
     * @Then /^The Revoked LPA details are not displayed$/
     */
    public function theRevokedLPADetailsAreNotDisplayed()
    {
        // Not needed for this context
    }

    /**
     * @When /^The LPA has been revoked$/
     * @Then /^I cannot see my access codes and their details$/
     */
    public function theStatusOfTheLpaGotRevoked()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I request to give an organisation access to the LPA whose status changed to Revoked$/
     * @Then /^I request to view an LPA whose status changed to Revoked$/
     */
    public function iRequestToGiveAnOrganisationAccessToTheLPAWhoseStatusChangedToRevoked()
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date'                 => 'date',
                        'lpa'                  => [],
                        'actor'                => $this->lpaData['actor'],
                    ]
                )
            )
        );
    }
}
