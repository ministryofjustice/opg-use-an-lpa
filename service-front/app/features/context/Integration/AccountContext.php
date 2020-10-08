<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Alphagov\Notifications\Client;
use BehatTest\Context\ActorContextTrait;
use Common\Exception\ApiException;
use Common\Service\Email\EmailClient;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\User\UserService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\RequestInterface;

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 *
 * @property string resetToken
 * @property string activationToken
 * @property string userPassword
 * @property string userEmail
 * @property string userPasswordResetToken
 * @property string lpa
 * @property string lpaJson
 * @property string lpaData
 * @property string passcode
 * @property string referenceNo
 * @property string userDob
 * @property string userIdentity
 * @property string actorLpaToken
 * @property int actorId
 * @property string $organisation
 * @property string $accessCode
 * @property string newUserEmail
 * @property string userEmailResetToken
 */
class AccountContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    /** @var MockHandler */
    private $apiFixtures;

    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    /** @var LpaService */
    private $lpaService;

    /** @var LpaFactory */
    private $lpaFactory;

    /** @var ViewerCodeService */
    private $viewerCodeService;

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->userService = $this->container->get(UserService::class);
        $this->emailClient = $this->container->get(EmailClient::class);
        $this->lpaService = $this->container->get(LpaService::class);
        $this->lpaFactory = $this->container->get(LpaFactory::class);
        $this->viewerCodeService = $this->container->get(ViewerCodeService::class);
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
                            'country'      => '',
                            'county'       => '',
                            'id'           => 0,
                            'postcode'     => '',
                            'town'         => '',
                            'type'         => 'Primary'
                        ]
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
                    'uId' => '700000000054'
                ],
            ],
            'lpa' => $this->lpa
        ];
    }

    /**
     * @Given /^I am currently signed in$/
     */
    public function iAmCurrentlySignedIn()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33w0rd';
        $this->userIdentity = '123';

        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id' => $this->userIdentity,
                'Email' => $this->userEmail,
                'LastLogin' => null
            ])));

        $user = $this->userService->authenticate($this->userEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userEmail = "test@example.com";
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When /^I ask for my password to be reset$/
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->userPasswordResetToken = '1234567890';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id'                 => '123',
                            'PasswordResetToken' => $this->userPasswordResetToken
                        ]
                    )
                )
            );

        $token = $this->userService->requestPasswordReset($this->userEmail);

        assertInternalType('string', $token);
        assertEquals($this->userPasswordResetToken, $token);
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $expectedUrl = 'http://localhost/forgot-password/' . $this->userPasswordResetToken;
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
 use ($expectedUrl, $expectedTemplateId) {
                $requestBody = $request->getBody()->getContents();

                assertContains($this->userPasswordResetToken, $requestBody);
                assertContains(json_encode($expectedUrl), $requestBody);
                assertContains($expectedTemplateId, $requestBody);
            });


        $this->emailClient->sendPasswordResetEmail($this->userEmail, $expectedUrl);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->userPasswordResetToken = '1234567890';
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->userPasswordResetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertTrue($canReset);
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->userPasswordResetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertFalse($canReset);
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $expectedPassword = 'newpassword';

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(function (RequestInterface $request, array $options) use ($expectedPassword) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($this->userPasswordResetToken, $params['token']);
                assertEquals($expectedPassword, $params['password']);
            });

        $this->userService->completePasswordReset($this->userPasswordResetToken, new HiddenString($expectedPassword));
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am unable to continue to reset my password$/
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I choose a new invalid password of "(.*)"$/
     */
    public function iChooseANewInvalid($password)
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am not a user of the lpa application$/
     */
    public function iAmNotAUserOfTheLpaApplication()
    {
        $this->userEmail = " ";
    }

    /**
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not needed for this context
    }

    /**
     * @When /^I create an account using duplicate details$/
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        // Not needed for this context
    }

    /**
     * @When /^I create an account$/
     */
    public function iCreateAnAccount()
    {
        $this->activationToken = 'activate1234567890';
        $this->userPassword = 'n3wPassWord';

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id'              => '123',
                            'activationToken' => $this->activationToken
                        ]
                    )
                )
            );

        $userData = $this->userService->create($this->userEmail, new HiddenString($this->userPassword));

        assertInternalType('string', $userData['activationToken']);
        assertEquals($this->activationToken, $userData['activationToken']);
    }

    /**
     * @When /^I request to add an LPA with valid details using (.*) which matches (.*)$/
     */
    public function iRequestToAddAnLPAWithValidDetailsUsing(string $code, string $storedCode)
    {
        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        $lpaData = $this->lpaService->getLpaByPasscode(
            $this->userIdentity,
            $storedCode,
            $this->referenceNo,
            $this->userDob
        );

        assertEquals(($lpaData['lpa'])->getUId(), $this->lpa['uId']);
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // Not needed for this context
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->actorLpaToken = '24680';
        $this->actorId = 9;

        // API call for adding an LPA
        $this->apiFixtures->post('/v1/actor-codes/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userIdentity])
                )
            );

        $actorCode = $this->lpaService->confirmLpaAddition($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);

        assertNotNull($actorCode);
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        try {
            $this->lpaService->getLpaByPasscode($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);
        } catch (ApiException $aex) {
            assertEquals($aex->getCode(), 404);
        }
    }

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        // Not needed for this context
    }

    /**
     * @When /^I fill in the form and click the cancel button$/
     */
    public function iFillInTheFormAndClickTheCancelButton()
    {
        // API call for finding all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $lpas = $this->lpaService->getLpas($this->userIdentity);

        assertEmpty($lpas);
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^The LPA has not been added$/
     */
    public function theLPAHasNotBeenAdded()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $expectedUrl = 'http://localhost/activate-account/' . $this->activationToken;
        $expectedTemplateId = 'd897fe13-a0c3-4c50-aa5b-3f0efacda5dc';

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
 use ($expectedUrl, $expectedTemplateId) {
                $requestBody = $request->getBody()->getContents();

                assertContains($this->activationToken, $requestBody);
                assertContains(json_encode($expectedUrl), $requestBody);
                assertContains($expectedTemplateId, $requestBody);
            });

        $this->emailClient->sendAccountActivationEmail($this->userEmail, $expectedUrl);
    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->activationToken = 'activate1234567890';
    }

    /**
     * @When /^I follow the instructions on how to activate my account$/
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        $this->apiFixtures->patch('/v1/user-activation')

            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'activation_token' => $this->activationToken
                        ]
                    )
                )
            )

            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->activationToken, $query);
            });

        $canActivate = $this->userService->activate($this->activationToken);
        assertTrue($canActivate);
    }

    /**
     * @Then /^my account is activated$/
     */
    public function myAccountIsActivated()
    {
        // Not needed for this context
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->activationTokenToken, $query);
            });

        $canActivate = $this->userService->activate($this->activationToken);
        assertFalse($canActivate);
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        // Not needed for this context
    }

    /**
     * @When /^I have provided required information for account creation such as (.*)(.*)(.*)(.*)(.*)$/
     */
    public function iHaveProvidedRequiredInformationForAccountCreationSuchAs($email1, $email2, $password1, $password2, $terms)
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told my account could not be created due to (.*)$/
     */
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo()
    {
        // Not needed for this context
    }

    /**
     * @When /^I create an account with a password of (.*)$/
     */
    public function iCreateAnAccountWithAPasswordOf($password)
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
     * @Given /^I am on the dashboard page$/
     */
    public function iAmOnTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->lpa['status'] = $status;

        // API call for getting the LPA by id
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            'user-lpa-actor-token' => $this->actorLpaToken,
                            'date' => 'date',
                            'lpa' => $this->lpa,
                            'actor' => $this->lpaData['actor']
                        ])
                )
            );
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            'code' => $this->accessCode,
                            'expires' => '2021-03-07T23:59:59+00:00',
                            'organisation' => $this->organisation
                        ])
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $lpa = $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        $codeData = $this->viewerCodeService->createShareCode($this->userIdentity, $this->actorLpaToken, $this->organisation);

        assertNotEmpty($lpa);
        assertEquals($this->accessCode, $codeData['code']);
        assertEquals($this->organisation, $codeData['organisation']);
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ]
                        ])
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
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I can see a flash message for the added LPA$/
     */
    public function iCanSeeAFlashMessageForTheAddedLPA()
    {
        // Not needed for this context
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
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        $this->lpaService->getLpaById($this->userIdentity, $this->actorLpaToken);

        // API call for getShareCodes in CheckAccessCodesHandler
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa['uId'],
                            'Added' => '2020-01-01T23:59:59+00:00',
                            'Expires' => '2021-01-01T23:59:59+00:00',
                            'Cancelled' => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode' => $this->accessCode,
                            'Viewed' => false,
                            'ActorId' => $this->actorId
                        ]
                    ])
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
     * @Given /^I have logged in previously$/
     */
    public function iHaveLoggedInPreviously()
    {
        $this->iAmCurrentlySignedIn();
    }

    /**
     * @When /^I sign in$/
     */
    public function iSignIn()
    {
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => $this->userIdentity,
                'Email'     => $this->userEmail,
                'LastLogin' => '2020-01-21T15:58:47+00:00'
            ])));

        $user = $this->userService->authenticate($this->userEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }
    /**
     * @Then /^I am taken to the dashboard page$/
     */
    public function iAmTakenToTheDashboardPage()
    {
        // API call for finding all the users added LPAs on dashboard
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $lpas = $this->lpaService->getLpas($this->userIdentity);

        assertEmpty($lpas);
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithStatus()
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
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
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa['uId'],
                            'Added' => '2020-01-01T23:59:59+00:00',
                            'Expires' => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->actorLpaToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode' => $this->accessCode,
                            'Viewed' => false,
                            'ActorId' => $this->actorId
                        ]
                    ])
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
     * @When /^One of the generated access code has expired$/
     */
    public function oneOfTheGeneratedAccessCodeHasExpired()
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
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        // API call for adding/checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        try {
            $this->lpaService->getLpaByPasscode($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);
        } catch (ApiException $aex) {
            assertEquals($aex->getCode(), 404);
        }
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        // Not needed for this context
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                          0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-01-02T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ]
                        ])
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to make code
        $this->apiFixtures->get('/v1/lpas/' . $this->actorLpaToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid' => $this->lpa['uId'],
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ],
                            1 => [
                                'SiriusUid'    => $this->lpa['uId'],
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Expires'      => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->actorLpaToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => "ABC321ABCXYZ",
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId
                            ]
                        ])
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
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
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
                    json_encode([
                        0 => $code1,
                        1 => $code2
                    ])
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
     * @Then /^I can see the relevant (.*) and (.*) of my access codes and their details$/
     */
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle)
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
                    json_encode([
                        'user-lpa-actor-token' => $this->actorLpaToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
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
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be able to click a link to go and create the access codes$/
     */
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes()
    {
        $this->iRequestToGiveAnOrganisationAccessToOneOfMyLPAs();
    }

    /**
     * @When /^I view my user details$/
     */
    public function iViewMyUserDetails()
    {
        // Not needed for this context
    }

    /**
     * @When /^I ask to change my password$/
     */
    public function iAskToChangeMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I provide my current password$/
     */
    public function iProvideMyCurrentPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I provide my new password$/
     */
    public function iProvideMyNewPassword()
    {
        $expectedPassword = 'S0meS0rt0fPassw0rd';

        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options) use ($expectedPassword) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($this->userIdentity, $params['user-id']);
                assertEquals($this->userPassword, $params['password']);
                assertEquals($expectedPassword, $params['new-password']);
            });
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        // Not needed in this context
    }

    /**
     * @When /^I provided incorrect current password$/
     */
    public function iProvidedIncorrectCurrentPassword()
    {
        $expectedPassword = 'S0meS0rt0fPassw0rd';

        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options) use ($expectedPassword) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($this->userIdentity, $params['user-id']);
                assertNotEquals($this->userPassword, $params['password']);
                assertEquals($expectedPassword, $params['new-password']);
            });
    }

    /**
     * @Then /^I am told my current password is incorrect$/
     */
    public function iAmToldMyCurrentPasswordIsIncorrect()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I am on the your details page$/
     */
    public function iAmOnTheYourDetailsPage()
    {
        //Not needed for this context
    }

    /**
     * @When /^I request to delete my account$/
     */
    public function iRequestToDeleteMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^My account is deleted$/
     */
    public function myAccountIsDeleted()
    {
        $userId = $this->userIdentity;

        // API call for deleting a user account
        $this->apiFixtures->delete('/v1/delete-account/' . $this->userIdentity)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => $this->userIdentity,
                'Email'     => $this->userEmail,
                'Password'  => $this->userPassword,
                'LastLogin' => null
            ])))
            ->inspectRequest(function (RequestInterface $request) use ($userId) {
                $uri = $request->getUri()->getPath();

                assertEquals($uri, '/v1/delete-account/123');
            });

        $delete = $this->userService->deleteAccount($this->userIdentity);
        assertNull($delete);
    }

    /**
     * @Given /^I am logged out of the service and taken to the deleted account confirmation page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12354abcde';
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([]))
            ) ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        try {
            $this->userService->requestChangeEmail($this->userIdentity, $this->newUserEmail, new HiddenString($this->userPassword));
        } catch (ApiException $aex) {
            assertEquals(403, $aex->getCode());
            return;
        }

        throw new ExpectationFailedException('Forbidden exception was not thrown for incorrect password');
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to change my email to an email address that is taken by another user on the service$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([]))
            ) ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        try {
            $this->userService->requestChangeEmail($this->userIdentity, $this->newUserEmail, new HiddenString($this->userPassword));
        } catch (ApiException $aex) {
            assertEquals(409, $aex->getCode());
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    /**
     * @Then /^I should be told my request was successful and an email is sent to the chosen email address to warn the user$/
     */
    public function iShouldBeToldMyRequestWasSuccessfulAndAnEmailIsSentToTheChosenEmailAddressToWarnTheUser()
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to change my email to one that another user has requested$/
     */
    public function iRequestToChangeMyEmailToOneThatAnotherUserHasRequested()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([]))
            ) ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        try {
            $this->userService->requestChangeEmail($this->userIdentity, $this->newUserEmail, new HiddenString($this->userPassword));
        } catch (ApiException $aex) {
            assertEquals(409, $aex->getCode());
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    /**
     * @When /^I request to change my email to one that another user has an expired request for$/
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        "EmailResetExpiry" => 1589983070,
                        "Email"            => $this->userEmail,
                        "LastLogin"        => null,
                        "Id"               => $this->userIdentity,
                        "NewEmail"         => $this->newUserEmail,
                        "EmailResetToken"  => "re3eTt0k3N",
                        "Password"         => $this->userPassword,
                    ])
                )
            ) ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        $data = $this->userService->requestChangeEmail($this->userIdentity, $this->newUserEmail, new HiddenString($this->userPassword));

        assertNotEmpty($data);
        assertEquals($this->userEmail, $data['Email']);
        assertEquals($this->newUserEmail, $data['NewEmail']);
        assertEquals($this->userIdentity, $data['Id']);
        assertEquals($this->userPassword, $data['Password']);
        assertArrayHasKey('EmailResetToken', $data);
        assertArrayHasKey('EmailResetExpiry', $data);
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail()
    {
        $currentEmailTemplateId = '19051f55-d60d-4bbc-ab49-cf85580d3102';
        $expectedUrl = 'http://localhost/verify-new-email/' . $this->userEmailResetToken;
        $newEmailTemplateId = 'bcf7e3f7-7f76-4e0a-87ee-b6722bdc223a';

        // API call for Notify sent to current email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
                use ($currentEmailTemplateId) {
                $requestBody = $request->getBody()->getContents();
                assertContains($currentEmailTemplateId, $requestBody);
            });

        $this->emailClient->sendRequestChangeEmailToCurrentEmail($this->userEmail, $this->newUserEmail);

        // API call for Notify sent to new email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
                use ($expectedUrl, $newEmailTemplateId) {
                $requestBody = $request->getBody()->getContents();

                assertContains($this->userEmailResetToken, $requestBody);
                assertContains(json_encode($expectedUrl), $requestBody);
                assertContains($newEmailTemplateId, $requestBody);
            });

        $this->emailClient->sendRequestChangeEmailToNewEmail($this->newUserEmail, $expectedUrl);
    }

    /**
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have requested to change my email address$/
     */
    public function iHaveRequestedToChangeMyEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid()
    {
        $this->userEmailResetToken = '12345abcde';
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress()
    {
        // API fixture for email reset token check
        $this->apiFixtures->get('/v1/can-reset-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userIdentity,
                        ]
                    )
                )
            );

        $canReset = $this->userService->canResetEmail($this->userEmailResetToken);
        assertTrue($canReset);
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset()
    {
        // API fixture to complete email change
        $this->apiFixtures->patch('/v1/complete-change-email')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $reset = $this->userService->completeChangeEmail($this->userEmailResetToken);

        assertNull($reset);
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => $this->userIdentity,
                'Email'     => $this->newUserEmail,
                'LastLogin' => '2020-01-21T15:58:47+00:00'
            ])));

        $user = $this->userService->authenticate($this->newUserEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }

    /**
     * @When /^I click the link to verify my new email address after my token has expired$/
     * @When /^I click an old link to verify my new email address containing a token that no longer exists$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired()
    {
        $this->userEmailResetToken = '12354abcde';
        // API fixture for email reset token check
        $this->apiFixtures->get('/v1/can-reset-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_GONE,
                    [],
                    json_encode([])
                )
            );

        $tokenValid = $this->userService->canResetEmail($this->userEmailResetToken);
        assertFalse($tokenValid);
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged()
    {
        // Not needed for this context
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33W0rd!123';

        // API call for creating an account
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([])));

        try {
            $this->userService->create($this->userEmail, new HiddenString($this->userPassword));
        } catch (ApiException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I access the account creation page$/
     */
    public function iAccessTheAccountCreationPage()
    {
        // Not needed for this context
    }
}
