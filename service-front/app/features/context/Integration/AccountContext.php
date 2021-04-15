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
    /** @var EmailClient */
    private $emailClient;
    /** @var LpaFactory */
    private $lpaFactory;
    /** @var LpaService */
    private $lpaService;
    /** @var UserService */
    private $userService;
    /** @var ViewerCodeService */
    private $viewerCodeService;

    /**
     * @Given /^I access the account creation page$/
     */
    public function iAccessTheAccountCreationPage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userEmail = "test@example.com";
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
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userIdentity,
                            'Email' => $this->userEmail,
                            'LastLogin' => null,
                        ]
                    )
                )
            );

        $user = $this->userService->authenticate($this->userEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am logged out of the service and taken to the deleted account confirmation page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage()
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
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12354abcde';
    }

    /**
     * @Given /^I am on the dashboard page$/
     */
    public function iAmOnTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the your details page$/
     */
    public function iAmOnTheYourDetailsPage()
    {
        //Not needed for this context
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        // Not needed for this context
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
     * @Then /^I am told my current password is incorrect$/
     */
    public function iAmToldMyCurrentPasswordIsIncorrect()
    {
        // Not needed in this context
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        // Not needed in this context
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
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
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
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
                            'Id' => '123',
                            'PasswordResetToken' => $this->userPasswordResetToken,
                        ]
                    )
                )
            );

        $token = $this->userService->requestPasswordReset($this->userEmail);

        assertInternalType('string', $token);
        assertEquals($this->userPasswordResetToken, $token);
    }

    /**
     * @When /^I ask to change my password$/
     */
    public function iAskToChangeMyPassword()
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
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $expectedPassword = 'newpassword';

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($expectedPassword) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($this->userPasswordResetToken, $params['token']);
                    assertEquals($expectedPassword, $params['password']);
                }
            );

        $this->userService->completePasswordReset($this->userPasswordResetToken, new HiddenString($expectedPassword));
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
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
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
                            'Id' => '123',
                            'activationToken' => $this->activationToken,
                        ]
                    )
                )
            );

        $userData = $this->userService->create($this->userEmail, new HiddenString($this->userPassword));

        assertInternalType('string', $userData['activationToken']);
        assertEquals($this->activationToken, $userData['activationToken']);
    }

    /**
     * @When /^I create an account using duplicate details$/
     */
    public function iCreateAnAccountUsingDuplicateDetails()
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
     * @When /^I create an account with a password of (.*)$/
     */
    public function iCreateAnAccountWithAPasswordOf($password)
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
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $query = $request->getUri()->getQuery();
                    assertContains($this->userPasswordResetToken, $query);
                }
            );

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertFalse($canReset);
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $query = $request->getUri()->getQuery();
                    assertContains($this->activationToken, $query);
                }
            );

        $canActivate = $this->userService->activate($this->activationToken);
        assertFalse($canActivate);
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $query = $request->getUri()->getQuery();
                    assertContains($this->userPasswordResetToken, $query);
                }
            );

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertTrue($canReset);
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
                            'activation_token' => $this->activationToken,
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $query = $request->getUri()->getQuery();
                    assertContains($this->activationToken, $query);
                }
            );

        $canActivate = $this->userService->activate($this->activationToken);
        assertTrue($canActivate);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->userPasswordResetToken = '1234567890';
    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->activationToken = 'activate1234567890';
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have logged in previously$/
     */
    public function iHaveLoggedInPreviously()
    {
        $this->iAmCurrentlySignedIn();
    }

    /**
     * @When /^I have provided required information for account creation such as (.*)(.*)(.*)(.*)(.*)$/
     */
    public function iHaveProvidedRequiredInformationForAccountCreationSuchAs(
        $email1,
        $email2,
        $password1,
        $password2,
        $terms
    ) {
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
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($expectedPassword) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($this->userIdentity, $params['user-id']);
                    assertEquals($this->userPassword, $params['password']);
                    assertEquals($expectedPassword, $params['new-password']);
                }
            );
    }

    /**
     * @When /^I provided incorrect current password$/
     */
    public function iProvidedIncorrectCurrentPassword()
    {
        $expectedPassword = 'S0meS0rt0fPassw0rd';

        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($expectedPassword) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertEquals($this->userIdentity, $params['user-id']);
                    assertNotEquals($this->userPassword, $params['password']);
                    assertEquals($expectedPassword, $params['new-password']);
                }
            );
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
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($expectedUrl, $expectedTemplateId) {
                    $requestBody = $request->getBody()->getContents();

                    assertContains($this->activationToken, $requestBody);
                    assertContains(json_encode($expectedUrl), $requestBody);
                    assertContains($expectedTemplateId, $requestBody);
                }
            );

        $this->emailClient->sendAccountActivationEmail($this->userEmail, $expectedUrl);
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
            ->inspectRequest(
                function (RequestInterface $request, array $options)
 use ($expectedUrl, $expectedTemplateId) {
                    $requestBody = $request->getBody()->getContents();

                    assertContains($this->userPasswordResetToken, $requestBody);
                    assertContains(json_encode($expectedUrl), $requestBody);
                    assertContains($expectedTemplateId, $requestBody);
                }
            );


        $this->emailClient->sendPasswordResetEmail($this->userEmail, $expectedUrl);
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
                    json_encode(
                        [
                            "EmailResetExpiry" => 1589983070,
                            "Email" => $this->userEmail,
                            "LastLogin" => null,
                            "Id" => $this->userIdentity,
                            "NewEmail" => $this->newUserEmail,
                            "EmailResetToken" => "re3eTt0k3N",
                            "Password" => $this->userPassword,
                        ]
                    )
                )
            )->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        $data = $this->userService->requestChangeEmail(
            $this->userIdentity,
            $this->newUserEmail,
            new HiddenString($this->userPassword)
        );

        assertNotEmpty($data);
        assertEquals($this->userEmail, $data['Email']);
        assertEquals($this->newUserEmail, $data['NewEmail']);
        assertEquals($this->userIdentity, $data['Id']);
        assertEquals($this->userPassword, $data['Password']);
        assertArrayHasKey('EmailResetToken', $data);
        assertArrayHasKey('EmailResetExpiry', $data);
    }

    /**
     * @When /^I request to change my email to an email address that is taken by another user on the service$/
     * @When /^I request to change my email to one that another user has requested$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([]))
            )->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        try {
            $this->userService->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $aex) {
            assertEquals(409, $aex->getCode());
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        $this->apiFixtures->patch('/v1/request-change-email')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([]))
            )->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        try {
            $this->userService->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $aex) {
            assertEquals(403, $aex->getCode());
            return;
        }

        throw new ExpectationFailedException('Forbidden exception was not thrown for incorrect password');
    }

    /**
     * @When /^I request to delete my account$/
     */
    public function iRequestToDeleteMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userIdentity,
                            'Email' => $this->newUserEmail,
                            'LastLogin' => '2020-01-21T15:58:47+00:00',
                        ]
                    )
                )
            );

        $user = $this->userService->authenticate($this->newUserEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
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
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($currentEmailTemplateId) {
                    $requestBody = $request->getBody()->getContents();
                    assertContains($currentEmailTemplateId, $requestBody);
                }
            );

        $this->emailClient->sendRequestChangeEmailToCurrentEmail($this->userEmail, $this->newUserEmail);

        // API call for Notify sent to new email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) use ($expectedUrl, $newEmailTemplateId) {
                    $requestBody = $request->getBody()->getContents();

                    assertContains($this->userEmailResetToken, $requestBody);
                    assertContains(json_encode($expectedUrl), $requestBody);
                    assertContains($newEmailTemplateId, $requestBody);
                }
            );

        $this->emailClient->sendRequestChangeEmailToNewEmail($this->newUserEmail, $expectedUrl);
    }

    /**
     * @Then /^I should be told my account could not be created due to (.*)$/
     */
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told my request was successful and an email is sent to the chosen email address to warn the user$/
     */
    public function iShouldBeToldMyRequestWasSuccessfulAndAnEmailIsSentToTheChosenEmailAddressToWarnTheUser()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful()
    {
        // Not needed for this context
    }

    /**
     * @When /^I sign in$/
     */
    public function iSignIn()
    {
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userIdentity,
                            'Email' => $this->userEmail,
                            'LastLogin' => '2020-01-21T15:58:47+00:00',
                        ]
                    )
                )
            );

        $user = $this->userService->authenticate($this->userEmail, $this->userPassword);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }

    /**
     * @When /^I view my user details$/
     */
    public function iViewMyUserDetails()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset()
    {
        // API fixture to complete email change
        $this->apiFixtures->patch('/v1/complete-change-email')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->userService->completeChangeEmail($this->userEmailResetToken);
    }

    /**
     * @Then /^my account is activated$/
     */
    public function myAccountIsActivated()
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
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => $this->userIdentity,
                            'Email' => $this->userEmail,
                            'Password' => $this->userPassword,
                            'LastLogin' => null,
                        ]
                    )
                )
            )
            ->inspectRequest(
                function (RequestInterface $request) use ($userId) {
                    $uri = $request->getUri()->getPath();

                    assertEquals($uri, '/v1/delete-account/123');
                }
            );

        $delete = $this->userService->deleteAccount($this->userIdentity);
        assertNull($delete);
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid()
    {
        $this->userEmailResetToken = '12345abcde';
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
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
     * @Then /^The (.*) LPA details and (.*) message are not displayed$/
     */
    public function theRevokedLPADetailsAndMessageAreNotDisplayed($status, $message)
    {
        // Not needed for this context
    }
}
