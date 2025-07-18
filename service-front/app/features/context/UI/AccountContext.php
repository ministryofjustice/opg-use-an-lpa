<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use BehatTest\Context\ContextUtilities;
use DateTime;
use DateTimeInterface;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;

class AccountContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    private const USER_SERVICE_ACTIVATE = 'UserService::activate';
    private const USER_SERVICE_CREATE = 'UserService::create';
    private const USER_SERVICE_REQUEST_CHANGE_EMAIL = 'UserService::requestChangeEmail';
    private const USER_SERVICE_CAN_RESET_EMAIL = 'UserService::canResetEmail';
    private const USER_SERVICE_COMPLETE_CHANGE_EMAIL = 'UserService::completeChangeEmail';
    private const USER_SERVICE_AUTHENTICATE = 'UserService::authenticate';
    private const LPA_SERVICE_GET_LPAS = 'LpaService::getLpas';
    private const USER_SERVICE_REQUEST_PASSWORD_RESET = 'UserService::requestPasswordReset';
    private const USER_SERVICE_CAN_PASSWORD_RESET = 'UserService::canPasswordReset';
    private const USER_SERVICE_COMPLETE_PASSWORD_RESET = 'UserService::completePasswordReset';
    private const USER_SERVICE_DELETE_ACCOUNT = 'UserService::deleteAccount';
    private const ONE_LOGIN_SERVICE_AUTHENTICATE = 'OneLoginService::authenticate';
    private const ONE_LOGIN_SERVICE_CALLBACK = 'OneLoginService::callback';
    private const ONE_LOGIN_SERVICE_LOGOUT = 'OneLoginService::logout';
    private const VIEWER_CODE_SERVICE_GET_SHARE_CODES = 'ViewerCodeService::getShareCodes';
    private const SYSTEM_MESSAGE_SERVICE_GET_MESSAGES = 'SystemMessageService::getMessages';

    private string $userEmail;
    private string $userPassword;
    private bool $userActive;
    private string $userId;
    private string $activationToken;
    private string $newUserEmail;
    private string $userEmailResetToken;
    private string $email;
    private string $password;
    private string $language;
    private array $systemMessages;

    #[Given('/^another user logs in$/')]
    public function anotherUserLogsIn(): void
    {
        $this->userEmail = 'anotheruser@test.com';
        $this->iAmCurrentlySignedIn();
    }

    #[Given('/^I access the login form$/')]
    public function iAccessTheLoginForm(): void
    {
        $this->ui->visit('/login');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertElementContainsText('button[name=sign-in]', 'Sign in');
    }

    #[When('/^I access the login page$/')]
    public function iAccessTheLoginPage(): void
    {
        $this->ui->visit('/login');
    }

    #[When('/^I access the use a lasting power of attorney web page$/')]
    public function iAccessTheUseALastingPowerOfAttorneyWebPage(): void
    {
        $this->iAmOnTheTriagePage();
    }

    #[Given('/^I am a user of the lpa application$/')]
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userEmail    = 'test@test.com';
        $this->userPassword = 'pa33w0rd';
        $this->userActive   = true;
        $this->userId       = '123';
    }

    #[Then('/^I am asked to confirm whether I am sure if I want to delete my account$/')]
    public function iAmAskedToConfirmWhetherIAmSureIfIWantToDeleteMyAccount(): void
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->assertPageContainsText('What happens if you delete your account');
    }

    #[Given('/^I am currently signed in$/')]
    #[Given('/^I chose to ignore setting cookies and I am on the dashboard page$/')]
    #[When('/^I sign in$/')]
    public function iAmCurrentlySignedIn(): void
    {
        $this->iHaveLoggedInToOneLogin('English');
        $this->iHaveAMatchingLocalAccount();
        $this->iAmSignedIn();
    }

    #[Then('/^I am directed to logout of one login$/')]
    public function iAmDirectedToLogoutOfOneLogin(): void
    {
        $locationHeader = $this->ui->getSession()->getResponseHeader('Location');
        assert::assertTrue(isset($locationHeader));
        $this->ui->assertResponseStatus(302);

        assert::assertStringContainsString(
            'http://fake.url/logout?id_token_hint=token',
            $locationHeader,
        );
    }

    #[Then('/^I am informed that there was a problem with that email address$/')]
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress(): void
    {
        $this->ui->assertPageAddress('/create-account-success');
        $this->ui->assertPageContainsText("We've emailed a link to " . $this->userEmail);
    }

    #[Given('/^I am logged out of the service and taken to the deleted account confirmation page$/')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage(): void
    {
        $this->ui->assertPageAddress('/delete-account');
        $this->ui->assertPageContainsText("We've deleted your account");
    }

    #[Given('/^I am not a user of the lpa application$/')]
    public function iAmNotAUserOfTheLpaApplication(): void
    {
        // Not needed for this context
    }

    #[When('/^I am not signed in to the use a lasting power of attorney service at this point$/')]
    public function iAmNotSignedInToTheUseALastingPowerOfAttorneyServiceAtThisPoint(): void
    {
        $this->ui->assertPageAddress('/login');
    }

    #[Given('/^I am on the actor privacy notice page$/')]
    public function iAmOnTheActorPrivacyNoticePage(): void
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    #[Given('/^I am on the actor terms of use page$/')]
    public function iAmOnTheActorTermsOfUsePage(): void
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    #[Given('/^I am on the change email page$/')]
    public function iAmOnTheChangeEmailPage(): void
    {
        $this->newUserEmail        = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';

        $this->ui->visit('/settings');

        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $link = $page->find('css', 'a[href="/change-email"]');
        if ($link === null) {
            throw new Exception('Change email link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/change-email');
    }

    #[Given('/^I am on the confirm account deletion page$/')]
    public function iAmOnTheConfirmAccountDeletionPage(): void
    {
        $this->iAmOnTheSettingsPage();
        $this->iRequestToDeleteMyAccount();
    }

    #[Given('/^I am on the stats page$/')]
    public function iAmOnTheStatsPage(): void
    {
        $this->ui->visit('/stats');
    }

    #[Given('/^I am on the triage page$/')]
    public function iAmOnTheTriagePage(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessages ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home');
    }

    #[Given('/^I am on the settings page$/')]
    public function iAmOnTheSettingsPage(): void
    {
        $this->ui->clickLink('Settings');
    }

    #[Then('/^I am redirected to the one login page$/')]
    public function iAmRedirectedToTheOneLoginPage(): void
    {
        $this->ui->assertPageAddress('/home');
    }

    #[When('/^I visit the homepage$/')]
    public function iVisitTheHomepage(): void
    {
        $this->ui->visit('/home');
    }

    #[Then('/^I am redirected to the LPA dashboard page$/')]
    public function iAmRedirectedToTheDashboardPage(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Given('/^I am signed in$/')]
    public function iAmSignedIn(): void
    {
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        if ($link === null) {
            throw new Exception('Sign out link not found');
        }
    }

    #[Then('/^I am taken back to the dashboard page$/')]
    public function iAmTakenBackToTheDashboardPage(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Then('/^I am taken back to the terms of use page$/')]
    public function iAmTakenBackToTheTermsOfUsePage(): void
    {
        $this->ui->assertPageAddress('/terms-of-use');
    }

    #[Then('/^I am taken back to the settings page$/')]
    public function iAmTakenBackToTheSettingsPage(): void
    {
        $this->ui->assertPageAddress('/settings');
        $this->ui->assertPageContainsText('Settings');
    }

    #[Then('/^I am taken to complete a satisfaction survey$/')]
    public function iAmTakenToCompleteASatisfactionSurvey(): void
    {
        $locationHeader = $this->ui->getSession()->getResponseHeader('Location');
        assert::assertTrue(isset($locationHeader));
        $this->ui->assertResponseStatus(302);

        assert::assertStringContainsString(
            'post_logout_redirect_uri=https://www.gov.uk/done/use-lasting-power-of-attorney',
            $locationHeader,
        );
    }

    #[Then('/^I am taken to the actor cookies page$/')]
    public function iAmTakenToTheActorCookiesPage(): void
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText('Use a lasting power of attorney service');
    }

    #[Then('/^I am taken to the create account page$/')]
    public function iAmTakenToTheCreateAccountPage(): void
    {
        $this->ui->assertPageAddress('/create-account');
        $this->ui->assertPageContainsText('Create an account');
    }

    #[Then('/^I am taken to the dashboard page$/')]
    public function iAmTakenToTheDashboardPage(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    #[Then('/^I am taken to the session expired page$/')]
    public function iAmTakenToTheSessionExpiredPage(): void
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText("We've signed you out");
    }

    #[Then('/^I am told my credentials are incorrect$/')]
    public function iAmToldMyCredentialsAreIncorrect(): void
    {
        $this->ui->assertPageContainsText('Check your details and try again. We could not find a Use a lasting ' .
                                          'power of attorney account with that email address and password.');
    }

    #[Then('/^I am told that my instructions have expired$/')]
    public function iAmToldThatMyInstructionsHaveExpired(): void
    {
        $this->ui->assertPageAddress('/reset-password/123456');

        $this->ui->assertPageContainsText('invalid or has expired');
    }

    #[Then('/^I am told that my password is invalid because it needs at least (.*)$/')]
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason): void
    {
        $this->ui->assertPageAddress('/reset-password/123456');

        $this->ui->assertPageContainsText($reason);
    }

    #[Given('/^I am unable to continue to reset my password$/')]
    public function iAmUnableToContinueToResetMyPassword(): void
    {
        // Not needed for this context
    }

    #[When('/^I ask for my password to be reset on an account that doesn\'t exist$/')]
    public function iAskForMyPasswordToBeResetWithAccountThatDoesntExist(
        $email = 'test@example.com',
        $email_confirmation = 'test@example.com',
    ): void {
        $this->ui->assertPageAddress('/reset-password');

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_NOT_FOUND,
                '',
                self::USER_SERVICE_REQUEST_PASSWORD_RESET
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    #[When('/^I ask for my password to be reset$/')]
    #[When('/^I ask for my password to be reset with below correct (.*) and (.*) details$/')]
    public function iAskForMyPasswordToBeReset(
        $email = 'test@example.com',
        $email_confirmation = 'test@example.com',
    ): void {
        $this->ui->assertPageAddress('/reset-password');

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'                 => $this->userId,
                        'PasswordResetToken' => '123456',
                    ]
                ),
                self::USER_SERVICE_REQUEST_PASSWORD_RESET
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    #[When('/^I ask for my password to be reset with below incorrect (.*) and (.*) details$/')]
    public function iAskForMyPasswordToBeResetWithBelowInCorrectEmailAndConfirmationEmailDetails(
        $email,
        $email_confirmation,
    ): void {
        $this->ui->assertPageAddress('/reset-password');

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_FORBIDDEN,
                json_encode([])
            )
        );

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    #[Given('/^I ask to change my password$/')]
    public function iAskToChangeMyPassword(): void
    {
        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $link = $page->find('css', 'a[href="change-password"]');
        if ($link === null) {
            throw new Exception('change password link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('change-password');

        $passwordInput = $page->find('css', 'input[type="password"]');

        if ($passwordInput === null) {
            throw new Exception('no password input box found');
        }
    }

    #[Then('/^I can change my email if required$/')]
    public function iCanChangeMyEmailIfRequired(): void
    {
        $this->ui->assertPageAddress('/settings');

        $this->ui->assertPageContainsText('Email address');
        $this->ui->assertPageContainsText($this->userEmail);

        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $changeEmailText = 'Change email address';
        $link            = $page->findLink($changeEmailText);
        if ($link === null) {
            throw new Exception($changeEmailText . ' link not found');
        }
    }

    #[Then('/^I can change my passcode if required$/')]
    public function iCanChangeMyPasscodeIfRequired(): void
    {
        $this->ui->assertPageAddress('/settings');

        $this->ui->assertPageContainsText('Password');

        $session = $this->ui->getSession();
        $page    = $session->getPage();

        $changePasswordtext = 'Change password';
        $link               = $page->findLink($changePasswordtext);
        if ($link === null) {
            throw new Exception($changePasswordtext . ' link not found');
        }
    }

    #[Then('/^I can see the accessibility statement for the Use service$/')]
    public function iCanSeeTheAccessibilityStatementForTheUseService(): void
    {
        $this->ui->assertPageContainsText('Accessibility statement for Use a lasting power of attorney');
    }

    #[Then('/^I can see the actor privacy notice$/')]
    public function iCanSeeTheActorPrivacyNotice(): void
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    #[Then('/^I can see the actor terms of use$/')]
    public function iCanSeeTheActorTermsOfUse(): void
    {
        $this->ui->assertPageAddress('/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
        $this->ui->assertPageContainsText('The service is for donors and attorneys on an LPA.');
    }

    #[Then('/^I can see user accounts table$/')]
    public function iCanSeeUserAccountsTable(): void
    {
        $this->ui->assertPageAddress('/stats');
        $this->ui->assertPageContainsText('Number of user accounts created and deleted');
    }

    #[Given('/^I choose a new invalid password of "(.*)"$/')]
    public function iChooseANewInvalid($password): void
    {
        $this->ui->assertPageAddress('/reset-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $this->ui->fillField('password', $password);
        $this->ui->pressButton('Change password');
    }

    #[Given('/^I choose a new password$/')]
    public function iChooseANewPassword(): void
    {
        $this->ui->assertPageAddress('/reset-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        // API fixture for password reset
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(['Id' => '123456']),
                self::USER_SERVICE_COMPLETE_PASSWORD_RESET
            )
        );

        $this->ui->fillField('password', '☺️✌Password!$');
        $this->ui->pressButton('Change password');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertArrayHasKey('token', $params);
        Assert::assertArrayHasKey('password', $params);
    }

    #[When('/^I click the (.*) link on the page$/')]
    public function iClickTheBackLinkOnThePage($backLink): void
    {
        $this->ui->clickLink($backLink);
    }

    #[When('/^I click the I already have an account link$/')]
    public function iClickTheIAlreadyHaveAnAccountLink(): void
    {
        $this->ui->clickLink('I already have an account');
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
                        'Id' => $this->userId,
                    ]
                ),
                self::USER_SERVICE_CAN_RESET_EMAIL
            )
        );

        // API fixture to complete email change
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::USER_SERVICE_COMPLETE_CHANGE_EMAIL
            )
        );

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    #[When('/^I click the link to verify my new email address after my token has expired$/')]
    #[When('/^I click an old link to verify my new email address containing a token that no longer exists$/')]
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired(): void
    {
        $this->userEmailResetToken = 'exp1r3dT0k3n';
        // API fixture for email reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_GONE,
                json_encode([]),
                self::USER_SERVICE_CAN_RESET_EMAIL
            )
        );

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    #[Given('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        $this->ui->assertPageAddress('/confirm-delete-account');

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userId,
                        'Email'     => $this->userEmail,
                        'Password'  => $this->userPassword,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_DELETE_ACCOUNT
            )
        );

        $this->ui->clickLink('Delete my account');
    }

    #[When('/^I create an account$/')]
    public function iCreateAnAccount(): void
    {
        $this->userEmail       = 'test@example.com';
        $this->password        = 'n3wPassWord!';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress($this->sharedState()->basePath . '/create-account');

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'              => '123',
                        'ActivationToken' => $this->activationToken,
                        'ExpiresTTL'      => 2553602798,
                    ]
                ),
                self::USER_SERVICE_CREATE
            )
        );

        // API call for Notify
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::USER_SERVICE_CREATE
            )
        );

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);

        // grab the button by selector as the button text might be in welsh or english.
        $button = $this->ui->getSession()->getPage()->find(
            'xpath',
            "//form[@name='create_account']//button[@type='submit']"
        );
        $button->press();
    }

    #[When('/^I create an account using duplicate details$/')]
    public function iCreateAnAccountUsingDuplicateDetails(): void
    {
        $this->userEmail       = 'test@example.com';
        $this->password        = 'n3wPassWord!';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode(
                    [
                        'Email'           => $this->userEmail,
                        'ActivationToken' => $this->activationToken,
                    ]
                ),
                self::USER_SERVICE_CREATE
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    #[When('/^I create an account using with an email address that has been requested for reset$/')]
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset(): void
    {
        $this->userEmail    = 'test@test.com';
        $this->userPassword = 'pa33W0rd!123';

        $this->ui->assertPageAddress('/create-account');

        // API call for creating an account
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode(
                    [
                        'message' => 'Another user has requested to change their email to ' . $this->userEmail,
                    ]
                ),
                self::USER_SERVICE_CREATE
            )
        );

        // API call for Notify to warn user their email an attempt to use their email has been made
        $this->apiFixtures->append(
            ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([]))
        );

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    #[When('/^I create an account with a password of (.*)$/')]
    public function iCreateAnAccountWithAPasswordOf($password): void
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('email', 'a@b.com');
        $this->ui->fillField('show_hide_password', $password);

        $this->ui->pressButton('Create account');
    }

    #[When('/^I enter correct credentials$/')]
    public function iEnterCorrectCredentials(): void
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode(
                        [
                            'Id'        => $this->userId,
                            'Email'     => $this->userEmail,
                            'LastLogin' => '2020-01-01',
                        ]
                    ),
                    'UserService::Authenticate'
                )
            );

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode([]),
                    self::LPA_SERVICE_GET_LPAS
                )
            );

            // Dashboard page checks system messages
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode($this->systemMessageData ?? []),
                    self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
                )
            );
        } else {
            // API call for authentication
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_UNAUTHORIZED,
                    json_encode([]),
                    self::USER_SERVICE_AUTHENTICATE
                )
            );
        }

        $this->ui->pressButton('Sign in');
    }

    #[When('I enter incorrect login email')]
    public function iEnterIncorrectLoginEmail(): void
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', 'inoc0rrectPassword');

        // API call for authentication
        $this->apiFixtures->append(
            ContextUtilities::newResponse(StatusCodeInterface::STATUS_NOT_FOUND, json_encode([]))
        );

        $this->ui->pressButton('Sign in');
    }

    #[When('/^I follow my unique expired instructions on how to reset my password$/')]
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword(): void
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->append(
            ContextUtilities::newResponse(StatusCodeInterface::STATUS_GONE, '', self::USER_SERVICE_CAN_PASSWORD_RESET)
        );

        $this->ui->visit('/reset-password/123456');
    }

    #[When('/^I follow my unique instructions on how to reset my password$/')]
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword(): void
    {
        // API fixture for reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id' => '123456',
                    ]
                ),
                self::USER_SERVICE_CAN_PASSWORD_RESET
            )
        );

        $this->ui->visit('/reset-password/123456');

        $this->ui->assertPageContainsText('Change your password');
    }

    #[When('/^I follow the instructions on how to activate my account$/')]
    public function iFollowTheInstructionsOnHowToActivateMyAccount(): void
    {
        $this->activationToken = 'abcd2345';
        $this->userEmail       = 'a@b.com';

        // API fixture for reset token check
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'               => '123',
                        'Email'            => $this->userEmail,
                        'activation_token' => $this->activationToken,
                    ]
                ),
                self::USER_SERVICE_ACTIVATE
            )
        );

        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->visit('/activate-account/' . $this->activationToken);

        //Test reset token check
        $request = $this->base->mockClientHistoryContainer[0]['request'];
        $params  = json_decode((string) $request->getBody()->getContents(), true);
        Assert::assertEquals('abcd2345', $params['activation_token']);
    }

    #[Given('/^I have asked for my password to be reset$/')]
    public function iHaveAskedForMyPasswordToBeReset(): void
    {
        //Not used
    }

    #[Given('I have asked to create a new account')]
    public function iHaveAskedToCreateANewAccount(): void
    {
        $this->email           = 'test@example.com';
        $this->password        = 'n3wPassWord!';
        $this->activationToken = 'activate1234567890';
    }

    #[Given('/^I have deleted my account$/')]
    public function iHaveDeletedMyAccount(): void
    {
        $this->iAmOnTheSettingsPage();
        $this->iRequestToDeleteMyAccount();
        $this->iConfirmThatIWantToDeleteMyAccount();
    }

    #[Given('/^I have not activated my account$/')]
    public function iHaveNotActivatedMyAccount(): void
    {
        $this->userActive = false;
    }

    #[Given('/^I have requested to change my email address$/')]
    public function iHaveRequestedToChangeMyEmailAddress(): void
    {
        // Not needed for this context
    }

    public function iDoNotFollowRedirects(): void
    {
        $this->ui->getSession()->getDriver()->getClient()->followRedirects(false);
    }

    public function iDoFollowRedirects(): void
    {
        $this->ui->getSession()->getDriver()->getClient()->followRedirects(true);
    }

    #[When('/^I logout of the application$/')]
    public function iLogoutOfTheApplication(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'redirect_uri' => 'http://fake.url/logout'
                            . '?id_token_hint=token'
                            . '&post_logout_redirect_uri=https://www.gov.uk/done/use-lasting-power-of-attorney',
                    ]
                ),
                self::ONE_LOGIN_SERVICE_LOGOUT
            )
        );

        //We cannot follow redirects to external links, returns page not found
        $this->iDoNotFollowRedirects();
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        $link->click();
        $this->iDoFollowRedirects();
    }

    #[When('/^I navigate to the actor cookies page$/')]
    public function iNavigateToTheActorCookiesPage(): void
    {
        $this->ui->clickLink('cookie policy');
    }

    #[Given('/^I provide my current password$/')]
    public function iProvideMyCurrentPassword(): void
    {
        $this->ui->fillField('current_password', $this->userPassword);
    }

    #[When('/^I provide my new password$/')]
    public function iProvideMyNewPassword(): void
    {
        $newPassword = 'Password123!$';

        // API call for password reset request
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::USER_SERVICE_AUTHENTICATE
            )
        );


        // API call for Notify
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->fillField('new_password', $newPassword);

        $this->ui->pressButton('Change password');
    }

    #[Then('/^I receive unique instructions on how to activate my account$/')]
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount(): void
    {
        $this->ui->assertPageAddress('/create-account-success');

        $this->ui->assertPageContainsText("We've emailed a link to " . $this->userEmail);

        Assert::assertIsString($this->activationToken);
        assert($this->apiFixtures->count() === 0);
    }

    #[Then('/^I receive unique instructions on how to reset my password$/')]
    #[Then('/^I receive an email telling me I do not have an account$/')]
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword(): void
    {
        $this->ui->assertPageAddress('/reset-password');

        $this->ui->assertPageContainsText("We've emailed a link to test@example.com");

        Assert::assertEquals(0, $this->apiFixtures->count());
    }

    #[Then('/^I receive unique instructions on how to reset my password to my provided (.*)$/')]
    public function iReceiveUniqueInstructionsOnHowToResetMyPasswordToMyProvidedEmail($email): void
    {
        $this->ui->assertPageAddress('/reset-password');
        $this->ui->assertPageContainsText('emailed a link to ' . strtolower((string) $email));
    }

    #[When('/^I attempt to login to my deleted account$/')]
    public function iRequestLoginToMyAccountThatWasDeleted(): void
    {
        $this->iHaveLoggedInToOneLogin('English');
        $this->iHaveAnEmailAddressThatDoesNotMatchALocalAccount();
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
                        'EmailResetExpiry' => time() + (60 * 60 * 48),
                        'Email'            => $this->userEmail,
                        'LastLogin'        => null,
                        'Id'               => $this->userId,
                        'NewEmail'         => $this->newUserEmail,
                        'EmailResetToken'  => $this->userEmailResetToken,
                        'Password'         => $this->userPassword,
                    ]
                ),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        // API call for Notify to current email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        // API call for Notify to new email
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    #[When('/^I request to change my email to an email address that is taken by another user on the service$/')]
    #[When('/^I request to change my email to one that another user has requested$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService(): void
    {
        //request change email call
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_CONFLICT,
                json_encode([]),
                self::USER_SERVICE_REQUEST_CHANGE_EMAIL
            )
        );

        // API call for Notify to new email requested
        $this->apiFixtures->append(ContextUtilities::newResponse(StatusCodeInterface::STATUS_OK, json_encode([])));

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');

        //Test for request change email
        $request = $this->base->mockClientHistoryContainer[3]['request'];
        $params  = json_decode((string) $request->getBody()->getContents(), true);

        Assert::assertIsArray($params);
        Assert::assertArrayHasKey('user-id', $params);
        Assert::assertArrayHasKey('new-email', $params);
        Assert::assertArrayHasKey('password', $params);
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

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', 'inC0rr3ct');
        $this->ui->pressButton('Save new email address');

        $request = $this->apiFixtures->getLastRequest();
        $params  = json_decode($request->getBody()->getContents(), true);
        Assert::assertIsArray($params);
        Assert::assertArrayHasKey('user-id', $params);
        Assert::assertArrayHasKey('new-email', $params);
        Assert::assertArrayHasKey('password', $params);
    }

    #[When('/^I request to create an account$/')]
    public function iRequestToCreateAnAccount(): void
    {
        $this->ui->clickLink('Create account');
    }

    #[When('/^I request to delete my account$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        $this->ui->assertPageAddress('/settings');

        $this->ui->clickLink('delete-account-link');
    }

    #[When('/^I request to go back to the terms of use page$/')]
    public function iRequestToGoBackToTheSpecifiedPage(): void
    {
        $this->ui->clickLink('Back');
    }

    #[When('/^I request to return to the settings page$/')]
    public function iRequestToReturnToTheSettingsPage(): void
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->clickLink('Return to settings');
    }

    #[When('/^I request to see the actor privacy notice$/')]
    public function iRequestToSeeTheActorPrivacyNoticePage(): void
    {
        $this->ui->clickLink('privacy notice');
    }

    #[When('/^I request to see the actor terms of use$/')]
    public function iRequestToSeeTheActorTermsOfUse(): void
    {
        $this->ui->clickLink('terms of the Use a lasting power of attorney service.');
    }

    #[Given('/^I should be able to login with my new email address$/')]
    #[Then('/^I see a flash message confirming my email address has been changed$/')]
    public function iShouldBeAbleToLoginWithMyNewEmailAddress(): void
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Email address changed successfully');
        // Login test is not needed since we already have one
    }

    #[Then('/^I should be sent an email to both my current and new email$/')]
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail(): void
    {
        // Not needed for this context
    }

    #[When('/^I should be taken to the (.*) page$/')]
    public function iShouldBeTakenToThePreviousPage($page): void
    {
        if ($page === 'triage') {
            $this->ui->assertPageAddress('/home');
        } elseif ($page === 'login') {
            $this->ui->assertPageAddress('/login');
        } elseif ($page === 'dashboard') {
            $this->ui->assertPageAddress('/lpa/dashboard');
        } elseif ($page === 'settings') {
            $this->ui->assertPageAddress('/settings');
        } elseif ($page === 'add a lpa') {
            $this->ui->assertPageAddress('/lpa/add-details');
        } elseif ($page === 'add by code') {
            $this->ui->assertPageAddress('/lpa/add-by-key');
        }
    }

    #[Then('/^I should be told my account could not be created due to (.*)$/')]
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo(string $reasons): void
    {
        $this->ui->assertPageAddress('/create-account');

        $this->ui->assertPageContainsText('' . $reasons);
    }

    #[Then('/^I should be told my email change request was successful$/')]
    public function iShouldBeToldMyEmailChangeRequestWasSuccessful(): void
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText("We've emailed a link to " . $this->newUserEmail);
    }

    #[Then('/^I should be told that I could not change my email because my password is incorrect$/')]
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect(): void
    {
        $this->ui->assertPageContainsText('The password you entered is incorrect');
    }

    #[Then('/^I should be told that my email could not be changed$/')]
    public function iShouldBeToldThatMyEmailCouldNotBeChanged(): void
    {
        $this->ui->assertPageContainsText('We cannot change your email address');
    }

    #[Given('/^I should be told that my request was successful$/')]
    public function iShouldBeToldThatMyRequestWasSuccessful(): void
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText("We've emailed a link to " . $this->newUserEmail);
    }

    #[Then('/^I should see the (.*) message$/')]
    public function iShouldSeeTheErrorMessage($error): void
    {
        $this->ui->assertPageAddress('/reset-password');
        $this->ui->assertPageContainsText($error);
    }

    #[When('/^I view my dashboard$/')]
    public function iViewMyDashboard(): void
    {
        // Dashboard page checks for all LPA's for a user
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->ui->visit('/lpa/dashboard');
    }

    #[When('/^I view my user details$/')]
    public function iViewMyUserDetails(): void
    {
        $this->ui->visit('/settings');
        $this->ui->assertPageContainsText('Settings');
    }

    #[Given('/^I want to create a new account$/')]
    public function iWantToCreateANewAccount(): void
    {
        // Not needed for this context
    }

    #[Then('/^I want to ensure cookie attributes are set$/')]
    public function iWantToEnsureCookieAttributesAreSet(): void
    {
        $session = $this->ui->getSession();

        // retrieving response headers:
        $cookies = $session->getResponseHeaders()['set-cookie'];

        foreach ($cookies as $value) {
            if (strstr($value, '__Host-session')) {
                Assert::assertStringContainsString('secure', $value);
                Assert::assertStringContainsString('httponly', $value);
                Assert::assertStringContainsString('path=/', $value);
                Assert::assertStringNotContainsString('domain', $value);
            } else {
                throw new Exception('Cookie named session not found in the response header');
            }
        }
    }

    #[Then('/^My account email address should be reset$/')]
    public function myAccountEmailAddressShouldBeReset(): void
    {
        // Not needed for this context
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // Not needed for this context
    }

    #[Given('/^My email reset token is still valid$/')]
    public function myEmailResetTokenIsStillValid(): void
    {
        $this->userEmailResetToken = '12345abcde';
    }

    #[Given('/^I am on the one login page$/')]
    public function iAmOnTheOneLoginPage(): void
    {
        $this->language = 'en';
        $this->ui->visit('/home');
        $this->ui->assertPageAddress('/home');
        $this->ui->assertElementOnPage('button[name=sign-in-one-login]');
    }

    #[When('/^I click the one login button$/')]
    public function iClickTheOneLoginButton(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'state' => 'fakestate',
                        'nonce' => 'fakenonce',
                        'url'   => 'http://fake.url/authorize',
                    ]
                ),
                self::ONE_LOGIN_SERVICE_AUTHENTICATE
            )
        );

        $this->iDoNotFollowRedirects();
        $this->ui->pressButton('sign-in-one-login');
        $this->iDoFollowRedirects();
    }

    #[When('/^I have logged in to one login in (English|Welsh)$/')]
    public function iHaveLoggedInToOneLogin($language): void
    {
        $this->userEmail = 'test@test.com';

        $this->iAmOnTheOneLoginPage();
        $this->language = $language === 'English' ? 'en' : 'cy';
        if ($this->language === 'cy') {
            $this->iSelectTheWelshLanguage();
        }
        $this->iClickTheOneLoginButton();
    }

    #[Then('/^I am redirected to the redirect page in (English|Welsh)$/')]
    public function iAmRedirectedToTheRedirectPage($language): void
    {
        $locationHeader = $this->ui->getSession()->getResponseHeader('Location');
        $request        = $this->apiFixtures->getLastRequest();
        $params         = $request->getUri()->getQuery();
        $language       = $language === 'English' ? 'en' : 'cy';

        assert::assertTrue(isset($locationHeader));
        assert::assertEquals($locationHeader, 'http://fake.url/authorize');
        assert::assertEquals($language, $this->language);
        assert::assertStringContainsString('ui_locale=' . $this->language, $params);
    }

    #[When('/^I select the Welsh language$/')]
    public function iSelectTheWelshLanguage(): void
    {
        $this->language = 'cy';
        $this->ui->clickLink('Cymraeg');
    }

    #[When('/^One Login returns a "(.*)" error$/')]
    public function oneLoginReturnsAError(string $errorType): void
    {
        $this->ui->visit('/home/login?error=' . $errorType . '&state=fakestate');
    }

    #[Then('/^I am redirected to the login page with a "(.*)" error and "(.*)"$/')]
    public function iAmRedirectedToTheLanguageErrorPage(string $errorType, $errorMessage): void
    {
        $basePath = $this->language === 'cy' ? '/cy' : '';
        $this->ui->assertPageAddress($basePath . '/home?error=' . $errorType);
        $this->ui->assertPageContainsText($errorMessage);
    }

    #[Then('/^I have an account whose sub matches a local account$/')]
    #[Then('/^I have an email address that matches a local account$/')]
    public function iHaveAMatchingLocalAccount(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user'  => [
                            'Id'        => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
                            'Identity'  => 'fakeSub',
                            'Email'     => $this->userEmail,
                            'LastLogin' => (new DateTime('-1 day'))->format(DateTimeInterface::ATOM),
                        ],
                        'token' => 'users_login_token',
                    ],
                ),
                self::ONE_LOGIN_SERVICE_CALLBACK
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]), // no LPAs
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home/login?code=FakeCode&state=FakeState');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
    }

    #[Then('/^I have an account whose sub matches a local account with LPAs$/')]
    #[Then('/^I have an email address that matches a local account with LPAs$/')]
    public function iHaveAMatchingLocalAccountWithLpas(): void
    {
        $lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json'));

        $userLpaActorToken = '987654321';

        $lpaData = [
            'user-lpa-actor-token'       => $userLpaActorToken,
            'date'                       => 'today',
            'actor'                      => [
                'type'    => 'primary-attorney',
                'details' => $lpa->attorneys[0],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance'     => false,
            'lpa'                        => $lpa,
            'added'                      => '2021-10-5 12:00:00',
        ];

        $dashboardLPAs = [$userLpaActorToken => $lpaData];

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user'  => [
                            'Id'        => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
                            'Identity'  => 'fakeSub',
                            'Email'     => $this->userEmail,
                            'LastLogin' => (new DateTime('-1 day'))->format(DateTimeInterface::ATOM),
                        ],
                        'token' => 'users_login_token',
                    ],
                ),
                self::ONE_LOGIN_SERVICE_CALLBACK
            )
        );
        //API call for getting all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($dashboardLPAs),
                self::LPA_SERVICE_GET_LPAS
            )
        );
        foreach ($dashboardLPAs as $lpa) {
            $this->apiFixtures->append(
                ContextUtilities::newResponse(
                    StatusCodeInterface::STATUS_OK,
                    json_encode([]),
                    self::VIEWER_CODE_SERVICE_GET_SHARE_CODES
                )
            );
        }

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home/login?code=FakeCode&state=FakeState');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
    }

    #[Then('/^I have an email address that does not match a local account$/')]
    public function iHaveAnEmailAddressThatDoesNotMatchALocalAccount(): void
    {
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'user'  => [
                            'Id'        => 'bf9e7e77-f283-49c6-a79c-65d5d309ef77',
                            'Identity'  => 'fakeSub',
                            'Email'     => 'fake@email.com',
                            'LastLogin' => (new DateTime('-1 day'))->format(DateTimeInterface::ATOM),
                        ],
                        'token' => 'users_login_token',
                    ],
                ),
                self::ONE_LOGIN_SERVICE_CALLBACK
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode($this->systemMessages ?? []),
                self::SYSTEM_MESSAGE_SERVICE_GET_MESSAGES
            )
        );

        $this->ui->visit('/home/login?code=FakeCode&state=FakeState');
    }

    #[Then('/^I see the LPA dashboard with any LPAs that are in the account$/')]
    public function iSeeTheLPADashboardWithAnyLPAsInAccount(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->clickLink('Add another LPA');
    }

    #[Then('/I see an empty LPA dashboard$/')]
    public function iSeeAnEmptyLPADashboard(): void
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->clickLink('Add your first LPA');
    }
}
