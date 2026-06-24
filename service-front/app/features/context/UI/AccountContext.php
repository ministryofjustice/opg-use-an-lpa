<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Mink\Driver\BrowserKitDriver;
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

    private const USER_SERVICE_AUTHENTICATE           = 'UserService::authenticate';
    private const LPA_SERVICE_GET_LPAS                = 'LpaService::getLpas';
    private const USER_SERVICE_DELETE_ACCOUNT         = 'UserService::deleteAccount';
    private const ONE_LOGIN_SERVICE_AUTHENTICATE      = 'OneLoginService::authenticate';
    private const ONE_LOGIN_SERVICE_CALLBACK          = 'OneLoginService::callback';
    private const ONE_LOGIN_SERVICE_LOGOUT            = 'OneLoginService::logout';
    private const VIEWER_CODE_SERVICE_GET_SHARE_CODES = 'ViewerCodeService::getShareCodes';
    private const SYSTEM_MESSAGE_SERVICE_GET_MESSAGES = 'SystemMessageService::getMessages';

    private string $userEmail;
    private string $userPassword;
    private bool $userActive;
    private string $userId;
    private string $language;

    /** @var array<mixed> */
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

    #[Given('/^I am logged out of the service and taken to the deleted account confirmation page$/')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage(): void
    {
        $this->ui->assertPageAddress('/delete-account');
        $this->ui->assertPageContainsText("We've deleted your account");
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

    #[When('/^I click the (.*) link on the page$/')]
    public function iClickTheBackLinkOnThePage($backLink): void
    {
        $this->ui->clickLink($backLink);
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
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_DELETE_ACCOUNT
            )
        );

        $this->ui->clickLink('Delete my account');
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

    #[Given('/^I have deleted my account$/')]
    public function iHaveDeletedMyAccount(): void
    {
        $this->iAmOnTheSettingsPage();
        $this->iRequestToDeleteMyAccount();
        $this->iConfirmThatIWantToDeleteMyAccount();
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

    #[When('/^I attempt to login to my deleted account$/')]
    public function iRequestLoginToMyAccountThatWasDeleted(): void
    {
        $this->iHaveLoggedInToOneLogin('English');
        $this->iHaveAnEmailAddressThatDoesNotMatchALocalAccount();
    }

    #[When('/^I request to delete my account$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        $this->ui->assertPageAddress('/settings');

        $this->ui->clickLink('delete-account-link');
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

    #[Then('/^I want to ensure cookie attributes are set$/')]
    public function iWantToEnsureCookieAttributesAreSet(): void
    {
        if (($driver = $this->ui->getSession()->getDriver()) instanceof BrowserKitDriver) {
            $client = $driver->getClient();
            $cookie = $client->getCookieJar()->get('__Host-session', '/', 'localhost');

            if ($cookie === null) {
                throw new Exception('Cookie named "session" not set');
            }

            Assert::assertEquals($cookie->getSameSite(), 'none');
            Assert::assertTrue($cookie->isHttpOnly());
            Assert::assertTrue($cookie->isSecure());
        } else {
            throw new PendingException('This test relies on the Mink driver being a BrowserKitDriver instance');
        }
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // Not needed for this context
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
