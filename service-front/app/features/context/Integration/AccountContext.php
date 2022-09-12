<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use BehatTest\Context\ActorContextTrait;
use BehatTest\Context\ContextUtilities;
use BehatTest\Context\UI\BaseUiContext;
use Common\Exception\ApiException;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
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
 * @property string activation_key
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

    private const USER_SERVICE_AUTHENTICATE = 'UserService::authenticate';
    private const USER_SERVICE_CREATE = 'UserService::create';
    private const LPA_SERVICE_GET_LPAS = 'LpaService::getLpas';
    private const USER_SERVICE_REQUEST_CHANGE_EMAIL = 'UserService::requestChangeEmail';
    private const USER_SERVICE_CAN_RESET_EMAIL = 'UserService::canResetEmail';
    private const USER_SERVICE_COMPLETE_CHANGE_EMAIL = 'UserService::completeChangeEmail';
    private const USER_SERVICE_DELETE_ACCOUNT = 'UserService::deleteAccount';
    private const USER_SERVICE_CHANGE_PASSWORD = 'UserService::changePassword';
    private const USER_SERVICE_REQUEST_PASSWORD_RESET = 'UserService::requestPasswordReset';
    private const USER_SERVICE_CAN_PASSWORD_RESET = 'UserService::canPasswordReset';
    private const USER_SERVICE_COMPLETE_PASSWORD_RESET = 'UserService::completePasswordReset';

    private LpaFactory $lpaFactory;
    private LpaService $lpaService;
    private UserService $userService;
    private ViewerCodeService $viewerCodeService;
    private NotifyService $notifyService;

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

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => $this->userIdentity,
                        'Email' => $this->userEmail,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => '123',
                        'PasswordResetToken' => $this->userPasswordResetToken,
                    ]
                ),
                self::USER_SERVICE_REQUEST_PASSWORD_RESET
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_COMPLETE_PASSWORD_RESET
            )
        );

        $this->userService->completePasswordReset($this->userPasswordResetToken, new HiddenString($expectedPassword));

        $request = $this->apiFixtures->getLastRequest();
        $params = json_decode($request->getBody()->getContents(), true);

        assertInternalType('array', $params);
        assertEquals($this->userPasswordResetToken, $params['token']);
        assertEquals($expectedPassword, $params['password']);
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress()
    {
        // API fixture for email reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => $this->userIdentity,
                    ]
                ),
                self::USER_SERVICE_CAN_RESET_EMAIL
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                json_encode([]),
                self::USER_SERVICE_CAN_RESET_EMAIL
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => '123',
                        'activationToken' => $this->activationToken,
                    ]
                ),
                self::USER_SERVICE_CREATE
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode([]),
                self::USER_SERVICE_CREATE
            )
        );

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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                '',
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertFalse($canReset);

        $request = $this->apiFixtures->getLastRequest();
        $query = $request->getUri()->getQuery();
        assertContains($this->userPasswordResetToken, $query);
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_GONE));

        $canActivate = $this->userService->activate($this->activationToken);
        assertFalse($canActivate);

        $request = $this->apiFixtures->getLastRequest();

        $query = $request->getUri()->getQuery();
        assertContains($this->activationToken, $query);
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
        assertTrue($canReset);

        $request = $this->apiFixtures->getLastRequest();
        $query = $request->getUri()->getQuery();
        assertContains($this->userPasswordResetToken, $query);
    }

    /**
     * @When /^I ask for my password to be reset on an account that doesn't exist$/
     */
    public function iAskForMyPasswordToBeResetOnAnAccountThatDoesntExist()
    {
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_NOT_FOUND));
    }

    /**
     * @When /^I follow the instructions on how to activate my account$/
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'activation_token' => $this->activationToken,
                    ]
                )
            )
        );

        $canActivate = $this->userService->activate($this->activationToken);
        assertTrue($canActivate);

        $request = $this->apiFixtures->getLastRequest();

        $query = $request->getUri()->getQuery();
        assertContains($this->activationToken, $query);
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

        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->userService->changePassword($this->userIdentity, new HiddenString($this->userPassword), new HiddenString($expectedPassword));

        $request = $this->apiFixtures->getLastRequest();
        $params = json_decode($request->getBody()->getContents(), true);

        assertInternalType('array', $params);
        assertEquals($this->userIdentity, $params['user-id']);
        assertEquals($this->userPassword, $params['password']);
        assertEquals($expectedPassword, $params['new-password']);
    }

    /**
     * @When /^I provided incorrect current password$/
     */
    public function iProvidedIncorrectCurrentPassword()
    {
        $expectedPassword = 'S0meS0rt0fPassw0rd';


        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_FORBIDDEN,
                json_encode([]),
                self::USER_SERVICE_CHANGE_PASSWORD
            )
        );

        try {
            $this->userService->changePassword('123', new HiddenString('SomeWrongValue'), new HiddenString($expectedPassword));
        } catch (ApiException $exception) {
            assertEquals($exception->getCode(), StatusCodeInterface::STATUS_FORBIDDEN);

            $request = $this->apiFixtures->getLastRequest();
            $params = json_decode($request->getBody()->getContents(), true);

            assertIsArray($params);
            assertEquals($this->userIdentity, $params['user-id']);
            assertNotEquals($this->userPassword, $params['password']);
            assertEquals($expectedPassword, $params['new-password']);
        }
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $this->userEmail = 'test@test.com';
        $expectedUrl = 'http://localhost/activate-account/' . $this->activationToken;
        $expectedTemplateId = 'd897fe13-a0c3-4c50-aa5b-3f0efacda5dc';

        $emailTemplate = 'AccountActivationEmail';

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result = $this->notifyService->sendEmailToUser(
                                $emailTemplate,
                                $this->userEmail,
            activateAccountUrl: $expectedUrl

        );

        $query = $this->apiFixtures->getLastRequest()->getBody()->getContents();

        assertContains('recipient', $query);
        assertContains('locale', $query);
        assertContains('http:\/\/localhost\/activate-account\/activate1234567890', $query);

        assertTrue($result);
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $expectedUrl = 'http://localhost/reset-password/' . $this->userPasswordResetToken;
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';
        $emailTemplate = 'PasswordResetEmail';

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result = $this->notifyService->sendEmailToUser(
                              $emailTemplate,
                              $this->userEmail,
            passwordResetUrl: $expectedUrl
        );
        $request = $this->apiFixtures->getLastRequest();
        $requestBody = $request->getBody()->getContents();

        assertContains($this->userEmail, $requestBody);
        assertContains('en_GB', $requestBody);

        assertTrue($result);
    }

    /**
     * @Then /^I receive an email telling me I do not have an account$/
     */
    public function iReceiveAnEmailTellingMeIDoNotHaveAnAccount()
    {
        $emailTemplate = 'NoAccountExistsEmail';

        $this->apiFixtures->reset();
        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result = $this->notifyService->sendEmailToUser(
            $emailTemplate,
            $this->userEmail
        );

        $request = $this->apiFixtures->getLastRequest();
        $requestBody = $request->getBody()->getContents();
        assertContains($this->userEmail, $requestBody);
        assertContains('en_GB', $requestBody);

        assertTrue($result);
    }

    /**
     * @When /^I request to change my email to one that another user has an expired request for$/
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
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
                ),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
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

        $request = $this->apiFixtures->getLastRequest();
        $params = json_decode($request->getBody()->getContents(), true);
        assertIsArray($params);
        assertArrayHasKey('user-id', $params);
        assertArrayHasKey('new-email', $params);
        assertArrayHasKey('password', $params);
    }

    /**
     * @When /^I request to change my email to an email address that is taken by another user on the service$/
     * @When /^I request to change my email to one that another user has requested$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode([]),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        try {
            $this->userService->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $aex) {
            assertEquals(409, $aex->getCode());

            $request = $this->apiFixtures->getLastRequest();
            $params = json_decode($request->getBody()->getContents(), true);
            assertIsArray($params);
            assertArrayHasKey('user-id', $params);
            assertArrayHasKey('new-email', $params);
            assertArrayHasKey('password', $params);
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_FORBIDDEN,
                json_encode([]),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        try {
            $this->userService->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $aex) {
            assertEquals(403, $aex->getCode());

            $request = $this->apiFixtures->getLastRequest();
            $params = json_decode($request->getBody()->getContents(), true);
            assertIsArray($params);
            assertArrayHasKey('user-id', $params);
            assertArrayHasKey('new-email', $params);
            assertArrayHasKey('password', $params);

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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => $this->userIdentity,
                        'Email' => $this->newUserEmail,
                        'LastLogin' => '2020-01-21T15:58:47+00:00',
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
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
        $emailTemplate1 = 'RequestChangeEmailToCurrentEmail';
        $emailTemplate2 = 'RequestChangeEmailToNewEmail';
        $expectedUrl = 'http://localhost/verify-new-email/' . $this->userEmailResetToken;

        // API call for Notify sent to current email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));
        $result = $this->notifyService->sendEmailToUser(
                             $emailTemplate1,
                             $this->userEmail,
            newEmailAddress: $this->newUserEmail
        );

        assertTrue($result);

        // API call for Notify sent to new email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));
        $result = $this->notifyService->sendEmailToUser(
                                    $emailTemplate2,
                                    $this->newUserEmail,
            completeEmailChangeUrl: $expectedUrl
        );
        assertTrue($result);
    }

    /**
     * @Then /^I should be told my account could not be created due to (.*)$/
     */
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told my email change request was successful$/
     */
    public function iShouldBeToldMyEmailChangeRequestWasSuccessful()
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => $this->userIdentity,
                        'Email' => $this->userEmail,
                        'LastLogin' => '2020-01-21T15:58:47+00:00',
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::USER_SERVICE_COMPLETE_CHANGE_EMAIL
            )
        );

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
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => $this->userIdentity,
                        'Email' => $this->userEmail,
                        'Password' => $this->userPassword,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_DELETE_ACCOUNT
            )
        );

        $delete = $this->userService->deleteAccount($this->userIdentity);
        assertNull($delete);

        $request = $this->apiFixtures->getLastRequest();
        $uri = $request->getUri()->getPath();

        assertEquals($uri, '/v1/delete-account/123');
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
        $this->lpaService = $this->container->get(LpaService::class);
        $this->lpaFactory = $this->container->get(LpaFactory::class);
        $this->viewerCodeService = $this->container->get(ViewerCodeService::class);
        $this->notifyService = $this->container->get(NotifyService::class);
    }
}
