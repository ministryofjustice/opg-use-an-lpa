<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\ActorContextTrait;
use BehatTest\Context\ContextUtilities;
use Common\Exception\ApiException;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 **/
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
    private const USER_SERVICE_REQUEST_PASSWORD_RESET = 'UserService::requestPasswordReset';
    private const USER_SERVICE_CAN_PASSWORD_RESET = 'UserService::canPasswordReset';
    private const USER_SERVICE_COMPLETE_PASSWORD_RESET = 'UserService::completePasswordReset';

    private LpaFactory $lpaFactory;
    private LpaService $lpaService;
    private UserService $userService;
    private ViewerCodeService $viewerCodeService;
    private NotifyService $notifyService;
    private string $activationToken;
    private string $userPassword;
    private string $userEmail;
    private string $userPasswordResetToken;
    private string $userIdentity;
    private string $newUserEmail;
    private string $userEmailResetToken;

    #[Given('/^I am a user of the lpa application$/')]
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userEmail = 'test@example.com';
    }

    #[Given('/^I am currently signed in$/')]
    public function iAmCurrentlySignedIn(): void
    {
        $this->userEmail    = 'test@test.com';
        $this->userPassword = 'pa33w0rd';
        $this->userIdentity = '123';

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userIdentity,
                        'Email'     => $this->userEmail,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
            )
        );

        $user = $this->container->get(UserService::class)->authenticate($this->userEmail, $this->userPassword);

        Assert::assertEquals($user->getIdentity(), $this->userIdentity);
    }

    #[Given('/^I am logged out of the service and taken to the deleted account confirmation page$/')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the change email page$/')]
    public function iAmOnTheChangeEmailPage(): void
    {
        $this->newUserEmail        = 'newEmail@test.com';
        $this->userEmailResetToken = '12354abcde';
    }

    #[Given('/^I am on the dashboard page$/')]
    public function iAmOnTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the settings page$/')]
    public function iAmOnTheSettingsPage(): void
    {
        //Not needed for this context
    }

    #[Then('/^I am taken back to the dashboard page$/')]
    public function iAmTakenBackToTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am taken to the dashboard page$/')]
    public function iAmTakenToTheDashboardPage(): void
    {
        // API call for finding all the users added LPAs on dashboard
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $lpas = $this->container->get(LpaService::class)->getLpas($this->userIdentity);

        Assert::assertEmpty($lpas);
    }

    #[Then('/^I am told my unique instructions to activate my account have expired$/')]
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am told that my instructions have expired$/')]
    public function iAmToldThatMyInstructionsHaveExpired(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am told that my password is invalid because it needs at least (.*)$/')]
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason): void
    {
        // Not needed for this context
    }

    #[Given('/^I am unable to continue to reset my password$/')]
    public function iAmUnableToContinueToResetMyPassword(): void
    {
        // Not needed for this context
    }

    #[When('/^I ask for my password to be reset$/')]
    public function iAskForMyPasswordToBeReset(): void
    {
        $this->userPasswordResetToken = '1234567890';

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'                 => '123',
                        'PasswordResetToken' => $this->userPasswordResetToken,
                    ]
                ),
                self::USER_SERVICE_REQUEST_PASSWORD_RESET
            )
        );

        $token = $this->container->get(UserService::class)->requestPasswordReset($this->userEmail);

        Assert::assertIsString($token);
        Assert::assertEquals($this->userPasswordResetToken, $token);
    }

    #[When('/^I ask to change my password$/')]
    public function iAskToChangeMyPassword(): void
    {
        // Not needed for this context
    }

    #[Given('/^I choose a new invalid password of "(.*)"$/')]
    public function iChooseANewInvalid($password): void
    {
        // Not needed for this context
    }

    #[Given('/^I choose a new password$/')]
    public function iChooseANewPassword(): void
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

        $this->container->get(UserService::class)->completePasswordReset($this->userPasswordResetToken, new HiddenString($expectedPassword));

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($this->userPasswordResetToken, $params['token']);
        Assert::assertEquals($expectedPassword, $params['password']);
    }

    #[When('/^I click the link to verify my new email address$/')]
    public function iClickTheLinkToVerifyMyNewEmailAddress(): void
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

        $canReset = $this->container->get(UserService::class)->canResetEmail($this->userEmailResetToken);
        Assert::assertTrue($canReset);
    }

    #[When('/^I click the link to verify my new email address after my token has expired$/')]
    #[When('/^I click an old link to verify my new email address containing a token that no longer exists$/')]
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired(): void
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

        $tokenValid = $this->container->get(UserService::class)->canResetEmail($this->userEmailResetToken);
        Assert::assertFalse($tokenValid);
    }

    #[When('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed for this context
    }

    #[When('/^I create an account$/')]
    public function iCreateAnAccount(): void
    {
        $this->activationToken = 'activate1234567890';
        $this->userPassword    = 'n3wPassWord!';

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'              => '123',
                        'activationToken' => $this->activationToken,
                    ]
                ),
                self::USER_SERVICE_CREATE
            )
        );

        $userData = $this->container->get(UserService::class)->create($this->userEmail, new HiddenString($this->userPassword));

        Assert::assertIsString($userData['activationToken']);
        Assert::assertEquals($this->activationToken, $userData['activationToken']);
    }

    #[When('/^I create an account using duplicate details$/')]
    public function iCreateAnAccountUsingDuplicateDetails(): void
    {
        // Not needed for this context
    }

    #[When('/^I fill in the form and click the cancel button$/')]
    public function iFillInTheFormAndClickTheCancelButton(): void
    {
        // API call for finding all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $lpas = $this->container->get(LpaService::class)->getLpas($this->userIdentity);

        Assert::assertEmpty($lpas);
    }

    #[When('/^I follow my unique expired instructions on how to reset my password$/')]
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                '',
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $canReset = $this->container->get(UserService::class)->canPasswordReset($this->userPasswordResetToken);
        Assert::assertFalse($canReset);

        $request = $this->apiFixtures->getLastRequest();
        $query   = $request->getUri()->getQuery();
        Assert::assertStringContainsString($this->userPasswordResetToken, $query);
    }

    #[When('/^I follow my unique instructions on how to reset my password$/')]
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $canReset = $this->container->get(UserService::class)->canPasswordReset($this->userPasswordResetToken);
        Assert::assertTrue($canReset);

        $request = $this->apiFixtures->getLastRequest();
        $query   = $request->getUri()->getQuery();
        Assert::assertStringContainsString($this->userPasswordResetToken, $query);
    }

    #[When('/^I ask for my password to be reset on an account that doesn\'t exist$/')]
    public function iAskForMyPasswordToBeResetOnAnAccountThatDoesntExist(): void
    {
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_NOT_FOUND, ''));
    }

    #[When('/^I follow the instructions on how to activate my account$/')]
    public function iFollowTheInstructionsOnHowToActivateMyAccount(): void
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

        $canActivate = $this->container->get(UserService::class)->activate($this->activationToken);
        Assert::assertTrue($canActivate);

        $request = $this->apiFixtures->getLastRequest();

        $query = $request->getUri()->getQuery();
        Assert::assertStringContainsString($this->activationToken, $query);
    }

    #[Given('/^I have asked for my password to be reset$/')]
    public function iHaveAskedForMyPasswordToBeReset(): void
    {
        $this->userPasswordResetToken = '1234567890';
    }

    #[Given('I have asked to create a new account')]
    public function iHaveAskedToCreateANewAccount(): void
    {
        $this->activationToken = 'activate1234567890';
    }

    #[Given('/^I have requested to change my email address$/')]
    public function iHaveRequestedToChangeMyEmailAddress(): void
    {
        // Not needed for this context
    }

    #[Given('/^I provide my current password$/')]
    public function iProvideMyCurrentPassword(): void
    {
        // Not needed for this context
    }

    #[Given('/^I provide my new password$/')]
    public function iProvideMyNewPassword(): void
    {
        $expectedPassword = 'S0meS0rt0fPassw0rd';

        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->container->get(UserService::class)->changePassword(
            $this->userIdentity,
            new HiddenString($this->userPassword),
            new HiddenString($expectedPassword)
        );

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertEquals($this->userIdentity, $params['user-id']);
        Assert::assertEquals($this->userPassword, $params['password']);
        Assert::assertEquals($expectedPassword, $params['new-password']);
    }

    #[Then('/^I receive unique instructions on how to activate my account$/')]
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount(): void
    {
        $this->userEmail = 'test@test.com';
        $expectedUrl     = 'http://localhost/activate-account/' . $this->activationToken;

        $emailTemplate = 'AccountActivationEmail';

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result = $this->container->get(NotifyService::class)->sendEmailToUser(
            $emailTemplate,
            $this->userEmail,
            activateAccountUrl: $expectedUrl
        );

        $query = $this->apiFixtures->getLastRequest()->getBody()->getContents();

        Assert::assertStringContainsString('recipient', $query);
        Assert::assertStringContainsString('locale', $query);
        Assert::assertStringContainsString('http:\/\/localhost\/activate-account\/activate1234567890', $query);

        Assert::assertTrue($result);
    }

    #[Then('/^I receive unique instructions on how to reset my password$/')]
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword(): void
    {
        $expectedUrl   = 'http://localhost/reset-password/' . $this->userPasswordResetToken;
        $emailTemplate = 'PasswordResetEmail';

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result      = $this->container->get(NotifyService::class)->sendEmailToUser(
            $emailTemplate,
            $this->userEmail,
            passwordResetUrl: $expectedUrl
        );
        $request     = $this->apiFixtures->getLastRequest();
        $requestBody = $request->getBody()->getContents();

        Assert::assertStringContainsString($this->userEmail, $requestBody);
        Assert::assertStringContainsString('en_GB', $requestBody);

        Assert::assertTrue($result);
    }

    #[Then('/^I receive an email telling me I do not have an account$/')]
    public function iReceiveAnEmailTellingMeIDoNotHaveAnAccount(): void
    {
        $emailTemplate = 'NoAccountExistsEmail';

        $this->apiFixtures->reset();
        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $result = $this->container->get(NotifyService::class)->sendEmailToUser(
            $emailTemplate,
            $this->userEmail
        );

        $request     = $this->apiFixtures->getLastRequest();
        $requestBody = $request->getBody()->getContents();
        Assert::assertStringContainsString($this->userEmail, $requestBody);
        Assert::assertStringContainsString('en_GB', $requestBody);

        Assert::assertTrue($result);
    }

    #[When('/^I request to change my email to one that another user has an expired request for$/')]
    #[When('/^I request to change my email to a unique email address$/')]
    public function iRequestToChangeMyEmailToAUniqueEmailAddress(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'EmailResetExpiry' => 1589983070,
                        'Email'            => $this->userEmail,
                        'LastLogin'        => null,
                        'Id'               => $this->userIdentity,
                        'NewEmail'         => $this->newUserEmail,
                        'EmailResetToken'  => 're3eTt0k3N',
                        'Password'         => $this->userPassword,
                    ]
                ),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        $data = $this->container->get(UserService::class)->requestChangeEmail(
            $this->userIdentity,
            $this->newUserEmail,
            new HiddenString($this->userPassword)
        );

        Assert::assertNotEmpty($data);
        Assert::assertEquals($this->userEmail, $data['Email']);
        Assert::assertEquals($this->newUserEmail, $data['NewEmail']);
        Assert::assertEquals($this->userIdentity, $data['Id']);
        Assert::assertEquals($this->userPassword, $data['Password']);
        Assert::assertArrayHasKey('EmailResetToken', $data);
        Assert::assertArrayHasKey('EmailResetExpiry', $data);

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);
        Assert::assertIsArray($params);
        Assert::assertArrayHasKey('user-id', $params);
        Assert::assertArrayHasKey('new-email', $params);
        Assert::assertArrayHasKey('password', $params);
    }

    #[When('/^I request to change my email to an email address that is taken by another user on the service$/')]
    #[When('/^I request to change my email to one that another user has requested$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode([]),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        try {
            $this->container->get(UserService::class)->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $apiException) {
            Assert::assertEquals(409, $apiException->getCode());

            $request = $this->apiFixtures->getLastRequest();
            $params  = json_decode($request->getBody()->getContents(), true);
            Assert::assertIsArray($params);
            Assert::assertArrayHasKey('user-id', $params);
            Assert::assertArrayHasKey('new-email', $params);
            Assert::assertArrayHasKey('password', $params);
            return;
        }

        throw new ExpectationFailedException('Conflict exception was not thrown');
    }

    #[When('/^I request to change my email with an incorrect password$/')]
    public function iRequestToChangeMyEmailWithAnIncorrectPassword(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_FORBIDDEN,
                json_encode([]),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        try {
            $this->container->get(UserService::class)->requestChangeEmail(
                $this->userIdentity,
                $this->newUserEmail,
                new HiddenString($this->userPassword)
            );
        } catch (ApiException $apiException) {
            Assert::assertEquals(403, $apiException->getCode());

            $request = $this->apiFixtures->getLastRequest();
            $params  = json_decode($request->getBody()->getContents(), true);
            Assert::assertIsArray($params);
            Assert::assertArrayHasKey('user-id', $params);
            Assert::assertArrayHasKey('new-email', $params);
            Assert::assertArrayHasKey('password', $params);

            return;
        }

        throw new ExpectationFailedException('Forbidden exception was not thrown for incorrect password');
    }

    #[When('/^I request to delete my account$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        // Not needed for this context
    }

    #[Given('/^I request to go back and try again$/')]
    public function iRequestToGoBackAndTryAgain(): void
    {
        // Not needed for this context
    }

    #[Given('/^I should be able to login with my new email address$/')]
    public function iShouldBeAbleToLoginWithMyNewEmailAddress(): void
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userIdentity,
                        'Email'     => $this->newUserEmail,
                        'LastLogin' => '2020-01-21T15:58:47+00:00',
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
            )
        );

        $user = $this->container->get(UserService::class)->authenticate($this->newUserEmail, $this->userPassword);

        Assert::assertEquals($user->getIdentity(), $this->userIdentity);
    }

    #[Then('/^I should be sent an email to both my current and new email$/')]
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail(): void
    {
        $emailTemplate1 = 'RequestChangeEmailToCurrentEmail';
        $emailTemplate2 = 'RequestChangeEmailToNewEmail';
        $expectedUrl    = 'http://localhost/verify-new-email/' . $this->userEmailResetToken;

        // API call for Notify sent to current email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));
        $result = $this->container->get(NotifyService::class)->sendEmailToUser(
            $emailTemplate1,
            $this->userEmail,
            newEmailAddress: $this->newUserEmail
        );

        Assert::assertTrue($result);

        // API call for Notify sent to new email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));
        $result = $this->container->get(NotifyService::class)->sendEmailToUser(
            $emailTemplate2,
            $this->newUserEmail,
            completeEmailChangeUrl: $expectedUrl
        );
        Assert::assertTrue($result);
    }

    #[Then('/^I should be told my email change request was successful$/')]
    public function iShouldBeToldMyEmailChangeRequestWasSuccessful(): void
    {
        // Not needed for this context
    }

    #[Then('/^I should be told that I could not change my email because my password is incorrect$/')]
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect(): void
    {
        // Not needed for this context
    }

    #[Then('/^I should be told that my email could not be changed$/')]
    public function iShouldBeToldThatMyEmailCouldNotBeChanged(): void
    {
        // Not needed for this context
    }

    #[Given('/^I should be told that my request was successful$/')]
    public function iShouldBeToldThatMyRequestWasSuccessful(): void
    {
        // Not needed for this context
    }

    #[When('/^I sign in$/')]
    public function iSignIn(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userIdentity,
                        'Email'     => $this->userEmail,
                        'LastLogin' => '2020-01-21T15:58:47+00:00',
                    ]
                ),
                self::USER_SERVICE_AUTHENTICATE
            )
        );

        $user = $this->container->get(UserService::class)->authenticate($this->userEmail, $this->userPassword);

        Assert::assertEquals($user->getIdentity(), $this->userIdentity);
    }

    #[When('/^I view my user details$/')]
    public function iViewMyUserDetails(): void
    {
        // Not needed for this context
    }

    #[Then('/^My account email address should be reset$/')]
    public function myAccountEmailAddressShouldBeReset(): void
    {
        // API fixture to complete email change
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::USER_SERVICE_COMPLETE_CHANGE_EMAIL
            )
        );

        $this->container->get(UserService::class)->completeChangeEmail($this->userEmailResetToken);
    }

    #[Then('/^my account is activated$/')]
    public function myAccountIsActivated(): void
    {
        // Not needed for this context
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // API call for deleting a user account
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userIdentity,
                        'Email'     => $this->userEmail,
                        'Password'  => $this->userPassword,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_DELETE_ACCOUNT
            )
        );

        $delete = $this->container->get(UserService::class)->deleteAccount($this->userIdentity);
        Assert::assertNull($delete);

        $request = $this->apiFixtures->getLastRequest();
        $uri     = $request->getUri()->getPath();

        Assert::assertEquals($uri, '/v1/delete-account/123');
    }

    #[Given('/^My email reset token is still valid$/')]
    public function myEmailResetTokenIsStillValid(): void
    {
        $this->userEmailResetToken = '12345abcde';
    }

    #[Then('/^my password has been associated with my user account$/')]
    public function myPasswordHasBeenAssociatedWithMyUserAccount(): void
    {
        // Not needed for this context
    }

    #[When('/^One of the generated access code has expired$/')]
    public function oneOfTheGeneratedAccessCodeHasExpired(): void
    {
        // Not needed for this context
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        // DO NOT use this method as below to create context global services out of the container
        // it breaks feature flag testing.
        // $this->lpaService = $this->container->get(LpaService::class); // DONT DO THIS

        // $apiFixtures and $awsFixtures are the exception
        $this->apiFixtures = $this->container->get(MockHandler::class);
    }
}
