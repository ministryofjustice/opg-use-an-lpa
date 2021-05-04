<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use BehatTest\Context\ActorContextTrait;
use Common\Exception\ApiException;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\AddLpa;
use Common\Service\Lpa\AddLpaApiResponse;
use Common\Service\Lpa\AddOlderLpa;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\OlderLpaApiResponse;
use Common\Service\Lpa\RemoveLpa;
use Common\Service\Lpa\ViewerCodeService;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 *
 * @property string lpa
 * @property string lpaJson
 * @property string lpaData
 * @property string passcode
 * @property string referenceNo
 * @property string userDob
 * @property string userIdentity
 * @property string actorLpaToken
 * @property int actorId
 * @property string organisation
 * @property string accessCode
 * @property string userPostCode
 * @property string userFirstname
 * @property string userSurname
 * @property string codeCreatedDate
 */
class LpaContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    /** @var MockHandler */
    private $apiFixtures;
    /** @var LpaFactory */
    private $lpaFactory;
    /** @var LpaService */
    private $lpaService;
    /** @var ViewerCodeService */
    private $viewerCodeService;

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
        $this->apiFixtures->delete('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                )
            );

        $removeLpa = $this->container->get(RemoveLpa::class);
        $result = $removeLpa($this->userIdentity, $this->actorLpaToken);

        assertArrayHasKey('lpa', $result);
        assertEquals('700000000054', $result['lpa']->getUId());
    }

    /**
     * @Given /^I confirm that I want to remove the LPA from my account$/
     */
    public function iConfirmThatIWantToRemoveTheLPAFromMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to remove an LPA from my account$/
     */
    public function iRequestToRemoveAnLPAFromMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^a letter is requested containing a one time use code$/
     */
    public function aLetterIsRequestedContainingAOneTimeUseCode()
    {
        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NO_CONTENT,
                    [],
                    ''
                )
            );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        try {
            $addOlderLpa(
                $this->userIdentity,
                intval($this->referenceNo),
                $this->userFirstname,
                $this->userSurname,
                DateTime::createFromFormat('Y-m-d', $this->userDob),
                $this->userPostCode,
            );
        } catch (ApiException $e) {
            throw new Exception(
                'Failed to correctly approve older LPA addition request: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @Given /^I already have a valid activation key for my LPA$/
     */
    public function iAlreadyHaveAValidActivationKeyForMyLPA()
    {
        $this->passcode = 'XYUPHWQRECHV';
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

        assertNotEmpty($lpa);
        assertEquals($this->accessCode, $codeData['code']);
        assertEquals($this->organisation, $codeData['organisation']);
    }

    /**
     * @Then /^I am informed that an LPA could not be found with these details$/
     */
    public function iAmInformedThatAnLPACouldNotBeFoundWithTheseDetails()
    {
        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    ''
                )
            );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        $result = $addOlderLpa(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
        );

        $response = new OlderLpaApiResponse(OlderLpaApiResponse::NOT_FOUND, []);

        assertEquals($response, $result);
    }

    /**
     * @Given /^I am on the add an LPA page$/
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
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'Bad Request',
                            'details' => 'LPA not eligible due to registration date',
                            'data' => [],
                        ]
                    )
                )
            );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        $result = $addOlderLpa(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
        );

        $response = new OlderLpaApiResponse(OlderLpaApiResponse::NOT_ELIGIBLE, []);

        assertEquals($response, $result);
    }

    /**
     * @Then /^I am told that I have an activation key for this LPA and where to find it$/
     * @Then /^I will be told that I have already requested this and the date I should receive the letter by$/
     */
    public function iAmToldThatIHaveAnActivationKeyForThisLPAAndWhereToFindIt()
    {
        // API call for requesting activation code
        $this->apiFixtures->patch('/v1/lpas/request-letter')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_BAD_REQUEST,
                    [],
                    json_encode(
                        [
                            'title' => 'Bad Request',
                            'details' => 'LPA not eligible as an activation key already exists',
                            'data' => [
                                'activation_key_created' => $this->codeCreatedDate
                            ],
                        ]
                    )
                )
            );

        $addOlderLpa = $this->container->get(AddOlderLpa::class);

        $result = $addOlderLpa(
            $this->userIdentity,
            intval($this->referenceNo),
            $this->userFirstname,
            $this->userSurname,
            DateTime::createFromFormat('Y-m-d', $this->userDob),
            $this->userPostCode,
        );

        $response = new OlderLpaApiResponse(
            OlderLpaApiResponse::HAS_ACTIVATION_KEY,
            ['activation_key_created' => $this->codeCreatedDate]
        );

        assertEquals($response, $result);
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
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        $code1 = [
            'SiriusUid' => $this->referenceNo,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->actorLpaToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => $code1Expiry,
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        $code2 = [
            'SiriusUid' => $this->referenceNo,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->actorLpaToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => $code2Expiry,
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->actorLpaToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => $code1,
                            1 => $code2,
                        ]
                    )
                )
            );

        $lpa = $this->lpaService->getLpas($this->userIdentity);

        $lpaObject = $this->lpaFactory->createLpaFromData($this->lpa);

        assertEquals($lpaObject, $lpa[$this->actorLpaToken]['lpa']);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, true);

        assertEquals($shareCodes[0], $code1);
        assertEquals($shareCodes[1], $code2);
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->actorLpaToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $lpa = $this->lpaService->getLpas($this->userIdentity);

        $lpaObject = $this->lpaFactory->createLpaFromData($this->lpa);

        assertEquals($lpaObject, $lpa[$this->actorLpaToken]['lpa']);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, true);

        assertEquals($shareCodes['activeCodeCount'], 0);
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
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        assertEmpty($shareCodes);
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-01-02T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);


        assertNotEmpty($lpa);
        assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        assertEquals(false, $shareCodes[0]['Viewed']);
        //check if the code expiry date is in the past
        assertGreaterThan(strtotime($shareCodes[0]['Expires']), strtotime((new DateTime('now'))->format('Y-m-d')));
        assertGreaterThan(strtotime($shareCodes[0]['Added']), strtotime($shareCodes[0]['Expires']));
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA/
     */
    public function iClickToCheckMyAccessCodeThatIsUsedToViewLPA()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => [
                                    0 => [
                                        'Viewed' => '2020-10-01T15:27:23.263483Z',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => $this->organisation,
                                    ],
                                    1 => [
                                        'Viewed' => '2020-10-01T15:27:23.263483Z',
                                        'ViewerCode' => $this->accessCode,
                                        'ViewedBy' => 'Another Organisation',
                                    ],
                                ],
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );
        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        assertNotEmpty($lpa['lpa']);
        assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);

        assertNotEmpty($shareCodes[0]['Viewed']);
        assertEquals($this->accessCode, $shareCodes[0]['Viewed'][0]['ViewerCode']);
        assertEquals($this->accessCode, $shareCodes[0]['Viewed'][1]['ViewerCode']);
        assertEquals($this->organisation, $shareCodes[0]['Viewed'][0]['ViewedBy']);
        assertEquals('Another Organisation', $shareCodes[0]['Viewed'][1]['ViewedBy']);
    }

    /**
     * @When /^I click to check my access codes$/
     */
    public function iClickToCheckMyAccessCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        assertNotEmpty($lpa['lpa']);
        assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        assertEquals(false, $shareCodes[0]['Viewed']);
    }

    /**
     * @When /^I click to check my active and inactive codes$/
     */
    public function iClickToCheckMyActiveAndInactiveCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                            1 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => "ABC321ABCXYZ",
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $shareCodes = $this->viewerCodeService->getShareCodes($this->userIdentity, $this->actorLpaToken, false);

        assertNotEmpty($lpa);
        assertEquals($this->accessCode, $shareCodes[0]['ViewerCode']);
        assertEquals($this->organisation, $shareCodes[0]['Organisation']);
        assertEquals($this->actorId, $shareCodes[0]['ActorId']);
        assertEquals($this->actorLpaToken, $shareCodes[0]['UserLpaActor']);
        assertEquals(false, $shareCodes[0]['Viewed']);

        assertEquals("ABC321ABCXYZ", $shareCodes[1]['ViewerCode']);
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        // API call for cancelShareCode in CancelCodeHandler
        $this->apiFixtures->put('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->viewerCodeService->cancelShareCode(
            $this->userIdentity,
            $this->actorLpaToken,
            $this->accessCode
        );

        // API call for getLpaById call happens inside of the check access codes handler
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        // API call for getShareCodes in CheckAccessCodesHandler
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'Cancelled' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $this->userIdentity,
            $this->actorLpaToken,
            false
        );

        assertEquals($shareCodes[0]['Organisation'], $this->organisation);
        assertEquals($shareCodes[0]['Cancelled'], '2021-01-01T23:59:59+00:00');
    }

    /**
     * @Given /^I confirm that those details are correct$/
     */
    public function iConfirmThatThoseDetailsAreCorrect()
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
        $this->iRequestToAddAnLPAWithValidDetailsUsing($this->passcode, $this->passcode);
        $this->theCorrectLPAIsFoundAndICanConfirmToAddIt();
        $this->theLPAIsSuccessfullyAdded();
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

        $this->passcode = ''; // reset this to blank as we won't have one normally
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpaJson = file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json');
        $this->lpa = json_decode($this->lpaJson, true);

        $this->passcode = 'XYUPHWQRECHV';
        $this->referenceNo = '700000000138';
        $this->userDob = '1975-10-05';
        $this->actorLpaToken = '24680';

        $this->lpaData = [
            'user-lpa-actor-token' => $this->actorLpaToken,
            'date' => 'today',
            'actor' => [
                'type' => 'primary-attorney',
                'details' => [
                    'addresses' => [
                        [
                            'addressLine1' => '',
                            'addressLine2' => '',
                            'addressLine3' => '',
                            'country' => '',
                            'county' => '',
                            'id' => 0,
                            'postcode' => '',
                            'town' => '',
                            'type' => 'Primary',
                        ],
                    ],
                    'companyName' => null,
                    'dob' => '1975-10-05',
                    'email' => 'test@test.com',
                    'firstname' => 'Ian',
                    'id' => 0,
                    'middlenames' => null,
                    'salutation' => 'Mr',
                    'surname' => 'Deputy',
                    'systemStatus' => true,
                    'uId' => '700000000054',
                ],
            ],
            'lpa' => $this->lpa,
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
        // Not needed for this context
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
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        $addLpa = $this->container->get(AddLpa::class);
        $lpaData = $addLpa->validate(
            $this->userIdentity,
            $storedCode,
            $this->referenceNo,
            $this->userDob
        );

        assertInstanceOf(AddLpaApiResponse::class, $lpaData);
        assertEquals(AddLpaApiResponse::ADD_LPA_FOUND, $lpaData->getResponse());
        assertEquals(($lpaData->getData()['lpa'])->getUId(), $this->lpa['uId']);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'code' => $this->accessCode,
                            'expires' => '2021-03-07T23:59:59+00:00',
                            'organisation' => $this->organisation,
                        ]
                    )
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->lpa['status'] = $status;

        if ($status === "Revoked") {
            // API call for getting the LPA by id
            $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'user-lpa-actor-token' => $this->actorLpaToken,
                                'date' => 'date',
                                'lpa' => [],
                                'actor' => $this->lpaData['actor'],
                            ]
                        )
                    )
                );
        } else {
            // API call for getting the LPA by id
            $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
                ->respondWith(
                    new Response(
                        StatusCodeInterface::STATUS_OK,
                        [],
                        json_encode(
                            [
                                'user-lpa-actor-token' => $this->actorLpaToken,
                                'date' => 'date',
                                'lpa' => $this->lpa,
                                'actor' => $this->lpaData['actor'],
                            ]
                        )
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
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor'],
                        ]
                    )
                )
            );

        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        assertNotNull($lpa);

        //API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId,
                            ],
                        ]
                    )
                )
            );

        $shareCodes = $this->viewerCodeService->getShareCodes(
            $this->userIdentity,
            $this->actorLpaToken,
            false
        );

        assertEquals($shareCodes[0]['Organisation'], $this->organisation);
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

        assertNotNull($lpa->lpa);
        assertNotNull($lpa->actor);
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
        $this->apiFixtures->post('/v1/add-lpa/validate')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode(
                        [
                            'title' => 'Not found',
                            'details' => 'Code validation failed',
                            'data' => [],
                        ]
                    )
                )
            );

        $addLpa = $this->container->get(AddLpa::class);

        $response = $addLpa->validate(
            $this->userIdentity,
            $this->passcode,
            $this->referenceNo,
            $this->userDob
        );

        assertEquals(AddLpaApiResponse::ADD_LPA_NOT_FOUND, $response->getResponse());
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->actorLpaToken = '24680';
        $this->actorId = 9;

        // API call for adding an LPA
        $this->apiFixtures->post('/v1/add-lpa/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userIdentity])
                )
            );

        $addLpa = $this->container->get(AddLpa::class);
        $response = $addLpa->confirm(
            $this->userIdentity,
            $this->passcode,
            $this->referenceNo,
            $this->userDob
        );

        assertEquals(AddLpaApiResponse::ADD_LPA_SUCCESS, $response->getResponse());
    }

    /**
     * @Given /^I requested an activation key within the last 14 days$/
     */
    public function iRequestedAnActivationKeyWithinTheLast14Days()
    {
        $this->codeCreatedDate = (new DateTime())->modify('-14 days')->format('Y-m-d');
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->lpaService = $this->container->get(LpaService::class);
        $this->lpaFactory = $this->container->get(LpaFactory::class);
        $this->viewerCodeService = $this->container->get(ViewerCodeService::class);

        // The user is signed in for all actions of this context
        $this->userIdentity = '123';
    }

    /**
     * @Then /^I receive an email confirming activation key request$/
     */
    public function iReceiveAnEmailConfirmingActivationKeyRequest()
    {
        // Not needed for this context
    }
}
