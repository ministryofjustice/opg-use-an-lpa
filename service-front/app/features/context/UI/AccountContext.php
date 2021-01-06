<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Message\RequestInterface;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\UI
 *
 * @property $userEmail
 * @property $userPassword
 * @property $lpa
 * @property $lpaData
 * @property $userId
 * @property $userLpaActorToken
 * @property $userActive
 * @property $actorId
 * @property $accessCode
 * @property $organisation
 * @property $newUserEmail
 * @property $userEmailResetToken
 * @property $activationToken
 */
class AccountContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /**
     * @Then /^I am taken to complete a satisfaction survey$/
     */
    public function iAmTakenToCompleteASatisfactionSurvey()
    {
        $this->ui->assertPageAddress('/done/use-lasting-power-of-attorney');
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/full_example.json'));

        $this->userLpaActorToken = '987654321';
        $this->actorId = 9;

        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
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
                            'type' => 'Primary'
                        ]
                    ],
                    'companyName' => null,
                    'dob' => '1975-10-05',
                    'email' => 'string',
                    'firstname' => 'Ian',
                    'id' => 0,
                    'middlenames' => null,
                    'salutation' => 'Mr',
                    'surname' => 'Deputy',
                    'systemStatus' => true,
                    'uId' => '700000000054'
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpa' => $this->lpa
        ];
    }

    /**
     * @Given /^I am the donor$/
     */
    public function iAmTheDonor()
    {
        $this->lpaData['actor']['type'] = 'donor';
        unset($this->lpaData['actor']['details']['systemStatus']);
    }

    /**
     * @Given /^I am inactive against the LPA on my account$/
     */
    public function iAmInactiveAgainstTheLpaOnMyAccount()
    {
        $this->lpaData['actor']['details']['systemStatus'] = false;
    }

    /**
     * @Given /^I access the login form$/
     */
    public function iAccessTheLoginForm()
    {
        $this->ui->visit('/login');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertElementContainsText('button[name=sign-in]', 'Sign in');
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33w0rd';
        $this->userActive = true;
        $this->userId = '123';
    }

    /**
     * @Given /^I am currently signed in$/
     * @When /^I sign in$/
     */
    public function iAmCurrentlySignedIn()
    {
        // do all the steps to sign in
        $this->iAccessTheLoginForm();
        $this->iEnterCorrectCredentials();
        $this->iAmSignedIn();
    }

    /**
     * @Then /^I am directed to my dashboard$/
     */
    public function iAmDirectedToMyPersonalDashboard()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^I am told my account has not been activated$/
     */
    public function iAmToldMyAccountHasNotBeenActivated()
    {
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);
    }

    /**
     * @Then /^I am told my credentials are incorrect$/
     */
    public function iAmToldMyCredentialsAreIncorrect()
    {
        $this->ui->assertPageContainsText('We cannot find an account with that email address and password');
    }

    /**
     * @When /^I attempt to sign in again$/
     */
    public function iAttemptToSignInAgain()
    {
        // Dashboard page checks for all LPA's for a user
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->visit('/login');
    }

    /**
     * @When /^I enter correct credentials$/
     */
    public function iEnterCorrectCredentials()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(
                    [
                        'Id' => $this->userId,
                        'Email' => $this->userEmail,
                        'LastLogin' => '2020-01-01'
                    ]
                )));

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->get('/v1/lpas')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->pressButton('Sign in');
    }

    /**
     * @Given /^I am signed in$/
     */
    public function iAmSignedIn()
    {
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        if ($link === null) {
            throw new \Exception('Sign out link not found');
        }
    }

    /**
     * @When I enter incorrect login password
     */
    public function iEnterIncorrectLoginPassword()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', "inoc0rrectPassword");

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When I enter incorrect login email
     */
    public function iEnterIncorrectLoginEmail()
    {
        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', "inoc0rrectPassword");

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        $this->iAccessTheLoginForm();
        $this->ui->assertPageAddress('/login');

        $this->ui->clickLink('Forgotten your password?');
    }

    /**
     * @When /^I ask for my password to be reset$/
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id'                 => $this->userId,
                            'PasswordResetToken' => '123456'
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('password-reset-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('email', 'test@example.com');
        $this->ui->fillField('email_confirm', 'test@example.com');
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @Given /^I have not activated my account$/
     */
    public function iHaveNotActivatedMyAccount()
    {
        $this->userActive = false;
    }

    /**
     * @When /^I logout of the application$/
     */
    public function iLogoutOfTheApplication()
    {
        $link = $this->ui->getSession()->getPage()->find('css', 'a[href="/logout"]');
        $link->click();
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->assertPageAddress('/forgot-password');

        $this->ui->assertPageContainsText('We\'ve emailed a link to test@example.com');

        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123456'
                        ]
                    )
                )
            );
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->visit('/forgot-password/123456');

        $this->ui->assertPageContainsText('Change your password');
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->getHandlers()->pop();
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE));

        $this->ui->visit('/forgot-password/123456');
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])));

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertArrayHasKey('token', $params);
                assertArrayHasKey('password', $params);
            });

        $this->ui->fillField('password', 'n3wPassWord');
        $this->ui->fillField('password_confirm', 'n3wPassWord');
        $this->ui->pressButton('Change password');
    }

    /**
     * @When /^I view my dashboard$/
     */
    public function iViewMyDashboard()
    {
        // Dashboard page checks for all LPA's for a user
        $request = $this->apiFixtures->get('/v1/lpas')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->setLastRequest($request);

        $this->ui->visit('/lpa/dashboard');
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        $this->ui->assertPageAddress('/login');
        // TODO when flash message are in place
        //$this->assertPageContainsText('Password successfully reset');

        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        $this->ui->assertPageContainsText('invalid or has expired');
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
        $this->ui->assertPageAddress('/forgot-password/123456');

        // API fixture for reset token check
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(['Id' => '123456'])));

        $this->ui->fillField('password', $password);
        $this->ui->fillField('password_confirm', $password);
        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->ui->assertPageAddress('/forgot-password/123456');

        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I view my user details$/
     */
    public function iViewMyUserDetails()
    {
        $this->ui->visit('/your-details');
        $this->ui->assertPageContainsText('Your details');
    }

    /**
     * @Then /^I can change my email if required$/
     */
    public function iCanChangeMyEmailIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Email address');
        $this->ui->assertPageContainsText($this->userEmail);

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $changeEmailText = 'Change email address';
        $link = $page->findLink($changeEmailText);
        if ($link === null) {
            throw new \Exception($changeEmailText . ' link not found');
        }
    }

    /**
     * @Then /^I can change my passcode if required$/
     */
    public function iCanChangeMyPasscodeIfRequired()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Password');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $changePasswordtext = "Change password";
        $link = $page->findLink($changePasswordtext);
        if ($link === null) {
            throw new \Exception($changePasswordtext . ' link not found');
        }
    }

    /**
     * @When /^I ask for a change of donors or attorneys details$/
     */
    public function iAskForAChangeOfDonorsOrAttorneysDetails()
    {
        $this->ui->assertPageAddress('/your-details');

        $this->ui->assertPageContainsText('Change a donor or attorney\'s details');
        $this->ui->clickLink('Change a donor or attorney\'s details');
    }

    /**
     * @Then /^Then I am given instructions on how to change donor or attorney details$/
     */
    public function iAmGivenInstructionOnHowToChangeDonorOrAttorneyDetails()
    {
        $this->ui->assertPageAddress('/lpa/change-details');
        $this->ui->assertPageContainsText('Let us know if a donor or attorney\'s details change');
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        $this->ui->visit('/lpa/add-by-code');
        $this->ui->assertPageAddress('/lpa/add-by-code');
    }

    /**
     * @When /^I request to add an LPA with valid details using (.*) which matches (.*)$/
     */
    public function iRequestToAddAnLPAWithValidDetailsUsing(string $code, string $storedCode)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            )
            ->inspectRequest(function (RequestInterface $request, array $options) use ($storedCode) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertEquals($storedCode, $params['actor-code']);
            });

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('passcode', $code);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA whose status is (.*) using (.*)$/
     */
    public function iRequestToAddAnLPAWhoseStatusIs(string $status, string $code)
    {
        $this->lpa->status = $status;

        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            )
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);
                assertEquals('XYUPHWQRECHV', $params['actor-code']);
            });

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('passcode', $code);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I see a page showing me the answers I have entered and content that helps me get it right$/
     */
    public function iSeeAPageShowingMeTheAnswersIHaveEnteredAndContentThatHelpsMeGetItRight()
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
        $this->ui->assertPageContainsText('LPA reference number: 700000000054');
        $this->ui->assertPageContainsText('Activation key: XYUPHWQRECHV');
        $this->ui->assertPageContainsText('Date of birth: 5 October 1975');
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // API call for adding an LPA
        $this->apiFixtures->post('/v1/actor-codes/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userLpaActorToken])
                )
            );

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->assertPageAddress('/lpa/check');

        $this->ui->assertPageContainsText('Is this the LPA you want to add?');
        $this->ui->assertPageContainsText('Mrs Ian Deputy Deputy');

        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('Ian Deputy Deputy');
        $this->ui->assertPageContainsText('Health and welfare');
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->ui->fillField('passcode', 'ABC321GHI567');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $this->ui->assertPageAddress('/lpa/check');
        $this->ui->assertPageContainsText('We could not find a lasting power of attorney');
    }

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        $this->ui->pressButton('Try again');
        $this->ui->assertPageAddress('/lpa/add');
    }

    /**
     * @When /^I request to add an LPA with an invalid passcode format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidPasscodeFormatOf1($passcode)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', $passcode);
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I am told that my input is invalid because (.*)$/
     */
    public function iAmToldThatMyInputIsInvalidBecause($reason)
    {
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I request to add an LPA with an invalid reference number format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidReferenceNumberFormatOf($referenceNo)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', $referenceNo);
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request to add an LPA with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidDOBFormatOf1($day, $month, $year)
    {
        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
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

        $this->ui->assertPageAddress('/lpa/add-by-code');
        $this->ui->fillField('passcode', 'T3STPA22C0D3');
        $this->ui->fillField('reference_number', '700000000001');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->clickLink('Cancel');
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Given /^The LPA has not been added$/
     */
    public function theLPAHasNotBeenAdded()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('Add your first LPA');
    }

    /**
     * @Given /^I am not a user of the lpa application$/
     */
    public function iAmNotAUserOfTheLpaApplication()
    {
        // Not needed for this context
    }

    /**
     * @When /^I create an account$/
     */
    public function iCreateAnAccount()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id' => '123',
                'Email' => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $this->ui->assertPageAddress('/create-account-success');

        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);

        assertInternalType('string', $this->activationToken);
        assertEquals(true, $this->apiFixtures->isEmpty());
    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';
    }

    /**
     * @When /^I follow the instructions on how to activate my account$/
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        $this->activationToken = 'abcd2345';
        $this->userEmail = 'a@b.com';
        // API fixture for reset token check
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id' => '123',
                            'Email' => $this->userEmail,
                            'activation_token' => $this->activationToken,
                        ]
                    )
                )
            )
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $params = json_decode($request->getBody()->getContents(), true);
                assertEquals('abcd2345', $params['activation_token']);
            });

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('personalisation', $params);
                    assertArrayHasKey('sign-in-url', $params['personalisation']);
                    assertContains('/login', $params['personalisation']['sign-in-url']);
                }
            );

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^my account is activated and I receive a confirmation email$/
     */
    public function myAccountIsActivatedAndIReceiveAConfirmationEmail()
    {
        $this->ui->assertPageContainsText('Account activated');
        $this->ui->assertPageContainsText('sign in');
    }

    /**
     * @When /^I follow my unique instructions after 24 hours$/
     */
    public function iFollowMyUniqueInstructionsAfter24Hours()
    {
        // remove successful reset token and add failure state
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(new Response(StatusCodeInterface::STATUS_NOT_FOUND));

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^I am told my unique instructions to activate my account have expired$/
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        $this->activationToken = 'activate1234567890';
        $this->ui->assertPageAddress('/activate-account/' . $this->activationToken);
        $this->ui->assertPageContainsText('We could not activate that account');
    }

    /**
     * @When /^I create an account using duplicate details$/
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->email = 'test@example.com';
        $this->password = 'n3wPassWord';
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([
                'Email' => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('show_hide_password', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @When /^I have provided required information for account creation such as (.*)(.*)(.*)$/
     */
    public function iHaveProvidedRequiredInformationForAccountCreationSuchAs($email, $password, $terms)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email);
        $this->ui->fillField('show_hide_password', $password);
        if ($terms === 1) {
            $this->ui->checkOption('terms');
        }

        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I should be told my account could not be created due to (.*)$/
     */
    public function iShouldBeToldMyAccountCouldNotBeCreatedDueTo($reasons)
    {
        $this->ui->assertPageAddress('/create-account');

        $this->ui->assertPageContainsText('' . $reasons);
    }

    /**
     * @When /^I create an account with a password of (.*)$/
     */
    public function iCreateAnAccountWithAPasswordOf($password)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', 'a@b.com');
        $this->ui->fillField('show_hide_password', $password);

        $this->ui->pressButton('Create account');
    }

    /**
     * @Given /^I am on the dashboard page$/
     */
    public function iAmOnTheDashboardPage()
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->ui->assertPageContainsText('View LPA summary');
        $this->lpa->status = $status;

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->clickLink('View LPA summary');
    }

    /**
     * @Then /^The full LPA is displayed with the correct (.*)$/
     */
    public function theFullLPAIsDisplayedWithTheCorrect($message)
    {
        $this->ui->assertPageAddress('/lpa/view-lpa');
        $this->ui->assertPageContainsText($message);
    }

    /**
     * @When /^I request to give an organisation access$/
     */
    public function iRequestToGiveAnOrganisationAccess()
    {
        $this->iAmOnTheDashboardPage();

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->assertPageAddress('lpa/dashboard');
        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call for get LpaById (when give organisation access is clicked)
        $this->iRequestToGiveAnOrganisationAccess();

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->userLpaActorToken . '/codes')
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
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->fillField('org_name', $this->organisation);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I have not provided required information for creating access code such as (.*)$/
     */
    public function iHaveNotProvidedRequiredInformationForCreatingAccessCodeSuchAs($organisationname)
    {
        $this->ui->assertPageContainsText("Which organisation do you want to give access to?");

        // API call to make code
        $this->apiFixtures->post('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date' => 'date',
                        'lpa' => $this->lpa,
                        'actor' => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->fillField('org_name', $organisationname);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I have logged in previously$/
     */
    public function iHaveLoggedInPreviously()
    {
        // do all the steps to sign in
        $this->iAccessTheLoginForm();

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(
                    [
                        'Id' => $this->userId,
                        'Email' => $this->userEmail,
                        'LastLogin' => null,
                    ]
                )));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->pressButton('Sign in');

        $this->iAmSignedIn();
        $this->iLogoutOfTheApplication();
    }

    /**
     * @Then /^I am taken to the dashboard page$/
     */
    public function iAmTakenToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @Then /^I should be told access code could not be created due to (.*)$/
     */
    public function iShouldBeToldAccessCodeCouldNotBeCreatedDueTo($reasons)
    {
        $this->ui->assertPageAddress('/lpa/code-make');

        $this->ui->assertPageContainsText($reasons);
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $this->ui->assertPageAddress('/lpa/code-make');
        $this->ui->assertPageContainsText('XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('Give this access code to ' . $this->organisation);
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
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => (new \DateTime('yesterday'))->format('c'),
                                'Expires' => (new \DateTime('+1 month'))->setTime(23, 59, 59)->format('c'),
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ]
                        ])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ]
                        ])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @When /^I click to check my active and inactive codes$/
     */
    public function iClickToCheckMyActiveAndInactiveCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2021-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => $this->accessCode,
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ],
                            1 => [
                                'SiriusUid' => $this->lpa->uId,
                                'Added' => '2020-01-01T23:59:59+00:00',
                                'Expires' => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode' => "ABC321ABCXYZ",
                                'Viewed' => false,
                                'ActorId' => $this->actorId
                            ]
                        ])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @Then /^I can see the relevant (.*) and (.*) of my access codes and their details$/
     */
    public function iCanSeeAllOfMyActiveAndInactiveAccessCodesAndTheirDetails($activeTitle, $inactiveTitle)
    {
        $this->ui->assertPageContainsText($activeTitle);
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');

        $this->ui->assertPageContainsText($inactiveTitle);
        $this->ui->assertPageContainsText('V - ABC3 - 21AB - CXYZ');
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
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
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText("Cancel organisation's access");
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->pressButton("Cancel organisation's access");

        $this->iWantToBeAskedForConfirmationPriorToCancellation();
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->ui->assertPageContainsText("Are you sure you want to cancel this code?");
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // API call to cancel code
        $this->apiFixtures->put('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa->uId,
                            'Added' => '2020-01-01T23:59:59+00:00',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode' => $this->accessCode,
                            'Cancelled' => '2021-01-02T23:59:59+00:00',
                            'Expires' => '2021-01-02T23:59:59+00:00',
                            'Viewed' => false,
                            'ActorId' => $this->actorId
                        ]
                    ])
                )
            );

        $this->ui->pressButton("Yes, cancel code");
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status (.*)/
     */
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus($status)
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $codeDetails = [];

        $codeSummary = $page->findAll('css', '.govuk-summary-list__row');
        foreach ($codeSummary as $codeItem) {
            $codeDetails[] = ($codeItem->find('css', 'dd'))->getText();
        }

        assertEquals($codeDetails[0], 'V - XYZ3 - 21AB - C987');
        assertEquals($codeDetails[1], 'Ian Deputy');
        assertEquals($codeDetails[2], 'Not viewed');
        assertEquals($codeDetails[4], $status);

        if ($codeDetails === null) {
            throw new \Exception('Code details not found');
        }
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        $this->iAmOnTheAddAnLPAPage();

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->ui->fillField('passcode', 'XYUPHWQRECHV');
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');

        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('Inactive codes');
        $this->ui->assertPageContainsText("V - XYZ3 - 21AB - C987");
        $this->ui->assertPageContainsText('Cancelled');
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        $this->ui->assertPageAddress('/lpa/confirm-cancel-code');

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa->uId,
                            'Added' => '2020-01-01T23:59:59+00:00',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode' => $this->accessCode,
                            'Expires' => '2021-01-05T23:59:59+00:00',
                            'Viewed' => false,
                            'ActorId' => $this->actorId,
                        ]
                    ])
                )
            );

        $this->ui->pressButton("No, return to access codes");
    }

    /**
     * @Then /^I should be taken back to the access code summary page/
     */
    public function iShouldBeTakenBackToTheAccessCodeSummaryPage()
    {
        $this->ui->assertPageContainsText('Check Access Codes');
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText("V - XYZ3 - 21AB - C987");
        $this->ui->assertPageNotContainsText('Cancelled');
    }

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        // Not needed for one this context
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        $code1 = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => $code1Expiry,
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        $code2 = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
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
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
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

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText($noActiveCodes);
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
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText('No organisations have access');
    }

    /**
     * @Then /^I should be told that I have not created any access codes yet$/
     */
    public function iShouldBeToldThatIHaveNotCreatedAnyAccessCodesYet()
    {
        $this->ui->assertPageContainsText('Check access codes');
        $this->ui->assertPageContainsText('There are no access codes for this LPA');
        $this->ui->assertPageContainsText('Give an organisation access');
    }

    /**
     * @When /^I check my access codes/
     */
    public function iCheckMyAccessCodes()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @Then /^I should be able to click a link to go and create the access codes$/
     */
    public function iShouldBeAbleToClickALinkToGoAndCreateTheAccessCodes()
    {
        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('Which organisation do you want to give access to');
    }

    /**
     * @Given /^I ask to change my password$/
     */
    public function iAskToChangeMyPassword()
    {
        $session = $this->ui->getSession();
        $page = $session->getPage();

        $link = $page->find('css', 'a[href="change-password"]');
        if ($link === null) {
            throw new \Exception('change password link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('change-password');

        $passwordInput = $page->find('css', 'input[type="password"]');

        if ($passwordInput === null) {
            throw new \Exception('no password input box found');
        }
    }

    /**
     * @Given /^I provide my current password$/
     */
    public function iProvideMyCurrentPassword()
    {
        $this->ui->fillField('current_password', $this->userPassword);
    }

    /**
     * @When /^I provide my new password$/
     */
    public function iProvideMyNewPassword()
    {
        $newPassword = 'Password123';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );


        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->fillField('new_password', $newPassword);
        $this->ui->fillField('new_password_confirm', $newPassword);

        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        $this->ui->assertPageAddress('your-details');
    }

    /**
     * @When /^I provided incorrect current password$/
     */
    public function iProvidedIncorrectCurrentPassword()
    {
        $newPassword = 'Password123';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('current_password', 'wrongPassword');
        $this->ui->fillField('new_password', $newPassword);
        $this->ui->fillField('new_password_confirm', $newPassword);

        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told my current password is incorrect$/
     */
    public function iAmToldMyCurrentPasswordIsIncorrect()
    {
        $this->ui->assertPageAddress('change-password');

        $this->ui->assertPageContainsText('Current password is incorrect');
    }

    /**
     * @Given /^I choose a new (.*) from below$/
     */
    public function iChooseANewPasswordFromGiven($password)
    {
        // API call for password reset request
        $this->apiFixtures->patch('/v1/change-password')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->fillField('new_password', $password);
        $this->ui->fillField('new_password_confirm', $password);

        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told that my new password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyNewPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        $this->ui->assertPageAddress('/change-password');

        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I enter correct email with '(.*)' and (.*) below$/
     */
    public function iEnterCorrectEmailWithEmailFormatAndPasswordBelow($email_format, $password)
    {
        $this->ui->fillField('email', $email_format);
        $this->ui->fillField('password', $password);

        if ($this->userActive) {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode(
                    [
                        'Id' => $this->userId,
                        'Email' => $email_format,
                        'LastLogin' => '2020-01-01'
                    ]
                )));

            // Dashboard page checks for all LPA's for a user
            $this->apiFixtures->get('/v1/lpas')
                ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));
        } else {
            // API call for authentication
            $this->apiFixtures->patch('/v1/auth')
                ->respondWith(new Response(StatusCodeInterface::STATUS_UNAUTHORIZED, [], json_encode([])));
        }

        $this->ui->assertPageContainsText('Sign in');
        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I hack the request id of the CSRF value$/
     */
    public function iHackTheRequestIdOfTheCSRFValue()
    {

        $value = $this->ui->getSession()->getPage()->find('css', '#__csrf')->getValue();
        $separated = explode('-', $value);
        $separated[1] = 'youhazbeenhaaxed'; //this is the requestid.
        $hackedValue = implode('-', $separated);
        $this->iEnterDetailsButHackTheCSRFTokenWith($hackedValue);
    }

    /**
     * @When /^I hack the token of the CSRF value$/
     */
    public function iHackTheTokenOfTheCSRFValue()
    {
        $value = $this->ui->getSession()->getPage()->find('css', '#__csrf')->getValue();

        $separated = explode('-', $value);
        $separated[0] = 'youhazbeenhaaxed'; //this is the token part.
        $hackedValue = implode("-", $separated);

        $this->iEnterDetailsButHackTheCSRFTokenWith($hackedValue);
    }

    /**
     * @When /^I hack the CSRF value with '(.*)'$/
     */
    public function iEnterDetailsButHackTheCSRFTokenWith($csrfToken)
    {

        $this->ui->getSession()->getPage()->find('css', '#__csrf')->setValue($csrfToken);

        $this->ui->assertPageContainsText('Sign in');
        $this->ui->pressButton('Sign in');
    }

    /**
     * @Then /^I should see relevant (.*) message$/
     */
    public function iShouldSeeRelevantErrorMessage($error)
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText($error);
    }

    /**
     * @When /^I enter incorrect login details with (.*) and (.*) below$/
     */
    public function iEnterInCorrectLoginDetailsWithEmailFormatAndPasswordBelow($emailFormat, $password)
    {
        $this->ui->fillField('email', $emailFormat);
        $this->ui->fillField('password', $password);

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @When /^I ask for my password to be reset with below correct (.*) and (.*) details$/
     */
    public function iAskForMyPasswordToBeResetWithBelowCorrectEmailAndConfirmationEmailDetails($email, $email_confirmation)
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                        'Id'                 => $this->userId,
                        'PasswordResetToken' => '123456'
                        ]
                    )
                )
            );

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('password-reset-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password to my provided (.*)$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPasswordToMyProvidedEmail($email)
    {
        $this->ui->assertPageAddress('/forgot-password');
        $this->ui->assertPageContainsText('emailed a link to ' . strtolower($email));
    }

    /**
     * @When /^I ask for my password to be reset with below incorrect (.*) and (.*) details$/
     */
    public function iAskForMyPasswordToBeResetWithBelowInCorrectEmailAndConfirmationEmailDetails($email, $email_confirmation)
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('email', $email);
        $this->ui->fillField('email_confirm', $email_confirmation);
        $this->ui->pressButton('Email me the link');
    }

    /**
     * @Then /^I should see the (.*) message$/
     */
    public function iShouldSeeTheErrorMessage($error)
    {
        $this->ui->assertPageAddress('/forgot-password');
        $this->ui->assertPageContainsText($error);
    }

    /**
     * @Then /^An account is created using (.*)(.*)(.*)(.*)$/
     */
    public function anAccountIsCreatedUsingEmail1Password1Password2Terms($email1, $password, $terms)
    {
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id' => '123',
                'Email' => $email1,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email1);
        $this->ui->fillField('password', $password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Given /^I am on the create account page$/
     */
    public function iAmOnTheCreateAccountPage()
    {
        $this->ui->visit('/create-account');
        $this->ui->assertPageAddress('/create-account');
    }

    /**
     * @When /^I request to see the actor terms of use$/
     */
    public function iRequestToSeeTheActorTermsOfUse()
    {
        $this->ui->clickLink('terms of use');
    }

    /**
     * @When /^I request to see the actor privacy notice$/
     */
    public function iRequestToSeeTheActorPrivacyNoticePage()
    {
        $this->ui->clickLink('privacy notice');
    }

    /**
     * @Then /^I can see the actor terms of use$/
     */
    public function iCanSeeTheActorTermsOfUse()
    {
        $this->ui->assertPageAddress('/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
        $this->ui->assertPageContainsText('The service is for donors and attorneys on an LPA.');
    }
    /**
     * @Then /^I can see the actor privacy notice$/
     */
    public function iCanSeeTheActorPrivacyNotice()
    {
        $this->ui->assertPageAddress('/privacy-notice');
        $this->ui->assertPageContainsText('Privacy notice');
    }

    /**
     * @Given /^I am on the actor terms of use page$/
     */
    public function iAmOnTheActorTermsOfUsePage()
    {
        $this->ui->visit('/terms-of-use');
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Given /^I am on the actor privacy notice page$/
     */
    public function iAmOnTheActorPrivacyNoticePage()
    {
        $this->ui->visit('/privacy-notice');
        $this->ui->assertPageAddress('/privacy-notice');
    }

    /**
     * @When /^I request to go back to the terms of use page$/
     */
    public function iRequestToGoBackToTheSpecifiedPage()
    {
        $this->ui->clickLink('Back');
    }

    /**
     * @Then /^I am taken back to the terms of use page$/
     */
    public function iAmTakenBackToTheTermsOfUsePage()
    {
        $this->ui->assertPageAddress('/terms-of-use');
    }

    /**
     * @Then /^I am taken to the triage page of the service$/
     */
    public function iAmTakenToTheTriagePage()
    {
        $this->ui->assertPageAddress('/home');
    }

    /**
     * @Given /^I am on the triage page$/
     */
    public function iAmOnTheTriagePage()
    {
        $this->ui->visit('/home');
    }

    /**
     * @When /^I request to create an account$/
     */
    public function iRequestToCreateAnAccount()
    {
        $this->ui->clickLink('Create account');
    }

    /**
     * @Then /^I am taken to the create account page$/
     */
    public function iAmTakenToTheCreateAccountPage()
    {
        $this->ui->assertPageAddress('/create-account');
        $this->ui->assertPageContainsText('Create an account');
    }

    /**
     * @When /^I click the I already have an account link$/
     */
    public function iClickTheIAlreadyHaveAnAccountLink()
    {
        $this->ui->clickLink('I already have an account');
    }

    /**
     * @Then /^I am allowed to login$/
     */
    public function iAmTakenToTheLoginPage()
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Sign in to your Use a lasting power of attorney account');
    }

    /**
     * @Then /^I am taken to the session expired page$/
     */
    public function iAmTakenToTheSessionExpiredPage()
    {
        $this->ui->assertPageAddress('/session-expired');
        $this->ui->assertPageContainsText('We\'ve signed you out');
    }

    /**
     * @Given /^I have added a (.*) LPA$/
     */
    public function iHaveAddedALPA($lpaType)
    {
        // Dashboard page

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');
    }

    /**
     * @When /^I request to give an organisation access for my (.*) LPA$/
     */
    public function iRequestToGiveAnOrganisationAccessForMyLPA($lpaType)
    {
        $this->lpa->caseSubtype = $lpaType;

        // API call for get LpaById (when give organisation access is clicked)
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->clickLink('Give an organisation access');
    }

    /**
     * @Then /^I should see relevant (.*) of organisations$/
     */
    public function iShouldSeeRelevantOfOrganisations($orgDescription)
    {
        $this->ui->assertPageAddress('lpa/code-make?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText($orgDescription);
    }

    /**
     * @Given /^I am on the full lpa page$/
     */
    public function iAmOnTheFullLpaPage()
    {
        $this->iAmOnTheDashboardPage();
        $this->iRequestToViewAnLPAWhichStatusIs('Registered');
        $this->theFullLPAIsDisplayedWithTheCorrect('This LPA is registered');
    }

    /**
     * @When /^I click the (.*) to change a donor or attorneys details$/
     */
    public function iClickTheToChangeADonorOrAttorneysDetails($link)
    {
        $this->ui->assertPageAddress('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
        $this->ui->clickLink($link);
    }

    /**
     * @Then /^I am taken to the change details page$/
     */
    public function iAmTakenToTheChangeDetailsPage()
    {
        $this->ui->assertPageAddress('lpa/change-details?lpa=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('Let us know if a donor or attorney\'s details change');
    }

    /**
     * @Given /^I am on the your details page$/
     */
    public function iAmOnTheYourDetailsPage()
    {
        $this->ui->clickLink('Your details');
    }

    /**
     * @When /^I request to delete my account$/
     */
    public function iRequestToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/your-details');
        $this->ui->clickLink('Delete account');
    }

    /**
     * @Then /^I am asked to confirm whether I am sure if I want to delete my account$/
     */
    public function iAmAskedToConfirmWhetherIAmSureIfIWantToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->assertPageContainsText('Are you sure you want to delete your account?');
    }

    /**
     * @Given /^I am on the confirm account deletion page$/
     */
    public function iAmOnTheConfirmAccountDeletionPage()
    {
        $this->iAmOnTheYourDetailsPage();
        $this->iRequestToDeleteMyAccount();
    }

    /**
     * @When /^I request to return to the your details page$/
     */
    public function iRequestToReturnToTheYourDetailsPage()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');
        $this->ui->clickLink('No, return to my details');
    }

    /**
     * @Then /^I am taken back to the your details page$/
     */
    public function iAmTakenBackToTheYourDetailsPage()
    {
        $this->ui->assertPageAddress('/your-details');
        $this->ui->assertPageContainsText('Your details');
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
    {
        $this->ui->assertPageAddress('/confirm-delete-account');

        $this->apiFixtures->delete('/v1/delete-account/' . $this->userId)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'Id' => $this->userId,
                        'Email' => $this->userEmail,
                        'Password' => $this->userPassword,
                        'LastLogin' => null
                    ])
                )
            );

        $this->ui->clickLink('Yes, continue deleting my account');
    }

    /**
     * @Then /^My account is deleted$/
     */
    public function myAccountIsDeleted()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am logged out of the service and taken to the deleted account confirmation page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage()
    {
        $this->ui->assertPageAddress('/delete-account');
        $this->ui->assertPageContainsText("We've deleted your account");
    }

    /**
     * @Given /^I have deleted my account$/
     */
    public function iHaveDeletedMyAccount()
    {
        $this->iAmOnTheYourDetailsPage();
        $this->iRequestToDeleteMyAccount();
        $this->iConfirmThatIWantToDeleteMyAccount();
    }

    /**
     * @When /^I request login to my account that was deleted$/
     */
    public function iRequestLoginToMyAccountThatWasDeleted()
    {
        $this->ui->visit('/login');

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);

        // API call for authentication
        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], json_encode([])));

        $this->ui->pressButton('Sign in');
    }

    /**
     * @Then /^My old account is not found$/
     */
    public function myOldAccountIsNotFound()
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('We cannot find an account with that email address and password');
    }

    /**
     * @Given /^an attorney can be removed from acting on a particular LPA$/
     */
    public function anAttorneyCanBeRemovedFromActingOnAParticularLpa()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I can see authority to use the LPA is revoked$/
     */
    public function iCanSeeAuthorityToUseTheLpaIsRevoked()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        $code = [
            'SiriusUid' => $this->lpa->uId,
            'Added' => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode' => $this->accessCode,
            'Expires' => '2024-01-01T23:59:59+00:00',
            'Viewed' => false,
            'ActorId' => $this->actorId,
        ];

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([0 => $code]))
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText('Access revoked');
        $this->ui->assertPageContainsText('You no longer have access to this LPA.');
    }

    /**
     * @Then /^I cannot make access codes for the LPA$/
     */
    public function iCannotMakeAccessCodesForTheLpa()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/code-make"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/code-make');
        }
    }

    /**
     * @Then /^I cannot check existing or inactive access codes for the LPA$/
     */
    public function iCannotCheckExistingOrInactiveAccessCodesForTheLpa()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/access-codes"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/access-codes');
        }
    }

    /**
     * @Then /^I cannot view the LPA summary$/
     */
    public function iCannotViewTheLpaSummary()
    {
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
            );

        $this->ui->visit('/lpa/dashboard');
        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);

        $this->ui->assertPageAddress('/lpa/dashboard');

        $links = $this->ui->getSession()->getPage()->findAll('css', 'a[href^="/lpa/view-lpa"]');
        if (count($links) > 0) {
            throw new AssertionFailedError('Expected not to find link: /lpa/view-lpa');
        }
    }

    /**
     * @Then /^I can find out why this LPA has been removed from the account$/
     */
    public function iCanFindOutWhyThisLPAHasBeenRemovedFromTheAccount()
    {
        $this->ui->clickLink('Why is this?');
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->assertPageContainsText('We\'ve removed an LPA from your account');
    }

    /**
     * @Then /^I can go back to the dashboard page$/
     */
    public function iCanGoBackToTheDashboardPage()
    {
        $this->ui->assertPageAddress('/lpa/removed');
        $this->ui->clickLink('Back');
        $this->ui->assertPageAddress('lpa/dashboard');
    }

    /**
     * @When /^I navigate to give an organisation access$/
     */
    public function iNavigateToGiveAnOrganisationAccess()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([ ])
                )
            );

        $this->ui->visit('lpa/code-make?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I navigate to check an access code$/
     */
    public function iNavigateToCheckAnAccessCode()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('lpa/access-codes?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @When /^I navigate to view the LPA summary$/
     */
    public function iNavigateToViewTheLpaSummary()
    {
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('lpa/view-lpa?lpa=' . $this->userLpaActorToken);
    }

    /**
     * @Then /^I am shown a not found error$/
     */
    public function iAmShownANotFoundError()
    {
        $this->ui->assertResponseStatus(404);
    }

    /**
     * @When /^I access the use a lasting power of attorney web page$/
     */
    public function iAccessTheUseALastingPowerOfAttorneyWebPage()
    {
        $this->iAmOnTheTriagePage();
    }

    /**
     * @When /^I am not signed in to the use a lasting power of attorney service at this point$/
     */
    public function iAmNotSignedInToTheUseALastingPowerOfAttorneyServiceAtThisPoint()
    {
        $this->ui->assertPageAddress('/login');
    }

    /**
     * @When /^I click the (.*) link on the page$/
     */
    public function iClickTheBackLinkOnThePage($backLink)
    {
        $this->ui->assertPageContainsText($backLink);
        $this->ui->clickLink($backLink);
    }

    /**
     * @When /^I should be taken to the (.*) page$/
     */
    public function iShouldBeTakenToThePreviousPage($page)
    {
        if ($page == 'triage') {
            $this->ui->assertPageAddress('/home');
        } elseif ($page == 'login') {
            $this->ui->assertPageAddress('/login');
        } elseif ($page == 'dashboard') {
            $this->ui->assertPageAddress('/lpa/dashboard');
        } elseif ($page == 'your details') {
            $this->ui->assertPageAddress('/your-details');
        } elseif ($page == 'add a lpa') {
            $this->ui->assertPageAddress('/lpa/add-details');
        } elseif ($page == 'add by code') {
            $this->ui->assertPageAddress('/lpa/add-by-code');
        }
    }

    /**
     * @When /^I am on the password reset page$/
     */
    public function iAmOnThePasswordResetPage()
    {
        $this->ui->assertPageContainsText('Reset your password');
    }

    /**
     * @Given /^I am on the check LPA page$/
     */
    public function iAmOnTheCheckLPAPage()
    {
        $this->ui->assertPageAddress('/lpa/check');
    }

    /**
     * @Then /^I want to ensure cookie attributes are set$/
     */
    public function iWantToEnsureCookieAttributesAreSet()
    {
        $session = $this->ui->getSession();

        // retrieving response headers:
        $cookies = $session->getResponseHeaders()['set-cookie'];

        foreach ($cookies as $value) {
            if (strstr($value, 'session')) {
                assertContains('secure', $value);
                assertContains('httponly', $value);
            } else {
                throw new Exception('Cookie named session not found in the response header');
            }
        }
    }

    /**
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage()
    {
        $this->newUserEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';

        $this->ui->visit('/your-details');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $link = $page->find('css', 'a[href="/change-email"]');
        if ($link === null) {
            throw new \Exception('Change email link not found');
        }

        $link->click();

        $this->ui->assertResponseStatus(StatusCodeInterface::STATUS_OK);
        $this->ui->assertPageAddress('/change-email');
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

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', 'inC0rr3ct');
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        $this->ui->assertPageContainsText('The password you entered is incorrect');
    }

    /**
     * @When /^I request to change my email to an invalid email$/
     */
    public function iRequestToChangeMyEmailToAnInvalidEmail()
    {
        $this->ui->fillField('new_email_address', 'invalidEmail.com');
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @Then /^I should be told that I could not change my email because the email is invalid$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseTheEmailIsInvalid()
    {
        $this->ui->assertPageContainsText('Enter an email address in the correct format, like name@example.com');
    }

    /**
     * @When /^I request to change my email to the same email of my account currently$/
     */
    public function iRequestToChangeMyEmailToTheSameEmailOfMyAccountCurrently()
    {
        $this->ui->fillField('new_email_address', $this->userEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @Then /^I should be told that I could not change my email because the email is the same as my current email$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseTheEmailIsTheSameAsMyCurrentEmail()
    {
        $this->ui->assertPageContainsText('The new email address you entered is the same as your current email address. They must be different.');
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
            ) ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);
                    assertInternalType('array', $params);
                    assertArrayHasKey('user-id', $params);
                    assertArrayHasKey('new-email', $params);
                    assertArrayHasKey('password', $params);
                }
            );

        // API call for Notify to new email requested
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                }
            );

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @Then /^I should be told my request was successful and an email is sent to the chosen email address to warn the user$/
     */
    public function iShouldBeToldMyRequestWasSuccessfulAndAnEmailIsSentToTheChosenEmailAddressToWarnTheUser()
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->newUserEmail);
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
                        "EmailResetExpiry" => time() + (60 * 60 * 48),
                        "Email"            => $this->userEmail,
                        "LastLogin"        => null,
                        "Id"               => $this->userId,
                        "NewEmail"         => $this->newUserEmail,
                        "EmailResetToken"  => $this->userEmailResetToken,
                        "Password"         => $this->userPassword,
                    ])
                )
            );

        // API call for Notify to current email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('new-email-address', $params['personalisation']);
                }
            );

        // API call for Notify to new email
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                    assertArrayHasKey('email_address', $params);
                    assertArrayHasKey('personalisation', $params);

                    assertInternalType('array', $params['personalisation']);
                    assertArrayHasKey('verify-new-email-url', $params['personalisation']);
                }
            );

        $this->ui->fillField('new_email_address', $this->newUserEmail);
        $this->ui->fillField('current_password', $this->userPassword);
        $this->ui->pressButton('Save new email address');
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful()
    {
        $this->ui->assertPageContainsText('Updating your email address');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->newUserEmail);
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
                            'Id' => $this->userId,
                        ]
                    )
                )
            );

        // API fixture to complete email change
        $this->apiFixtures->patch('/v1/complete-change-email')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress()
    {
        $this->ui->assertPageAddress('/login');
        // Login test is not needed since we already have one
    }

    /**
    * @When /^I click the link to verify my new email address after my token has expired$/
    * @When /^I click an old link to verify my new email address containing a token that no longer exists$/
    */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired()
    {
        $this->userEmailResetToken = 'exp1r3dT0k3n';
        // API fixture for email reset token check
        $this->apiFixtures->get('/v1/can-reset-email')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_GONE,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/verify-new-email/' . $this->userEmailResetToken);
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged()
    {
        $this->ui->assertPageContainsText("We cannot change your email address");
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $this->userEmail = 'test@test.com';
        $this->userPassword = 'pa33W0rd!123';

        $this->ui->assertPageAddress('/create-account');

        // API call for creating an account
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_CONFLICT, [], json_encode([
                'message' => 'Another user has requested to change their email to ' . $this->userEmail
            ])));

        // API call for Notify to warn user their email an attempt to use their email has been made
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(
                function (RequestInterface $request, array $options) {
                    $params = json_decode($request->getBody()->getContents(), true);

                    assertInternalType('array', $params);
                    assertArrayHasKey('template_id', $params);
                }
            );

        $this->ui->fillField('email', $this->userEmail);
        $this->ui->fillField('password', $this->userPassword);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        $this->ui->assertPageAddress('/create-account-success');
        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->userEmail);
    }

    /**
     * @Given /^I am on the stats page$/
     */
    public function iAmOnTheStatsPage()
    {
        $this->ui->visit('/stats');
    }

    /**
     * @Then /^I can see user accounts table$/
     */
    public function iCanSeeUserAccountsTable()
    {
        $this->ui->assertPageAddress('/stats');
        $this->ui->assertPageContainsText('Number of user accounts created and deleted');
    }

    /**
     * @Then /^I can see the message (.*)$/
     * <Important: This lpa has instructions or preferences>
     */
    public function iCanSeeTheMessage($message)
    {
        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->visit('/lpa/dashboard');

        $this->ui->assertPageAddress('/lpa/dashboard');
        $this->ui->assertPageContainsText($message);
    }

    /**
     * @Then /^I can see (.*) link along with the instructions or preference message$/
     */
    public function iCanSeeReadMoreLink($readMoreLink)
    {
        $this->ui->assertPageAddress('/lpa/dashboard');

        $this->ui->assertPageContainsText('Important: This lpa has instructions or preferences');

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $readMoreLink = $page->findLink($readMoreLink);
        if ($readMoreLink === null) {
            throw new \Exception($readMoreLink . ' link not found');
        }
    }

    /**
     * @When /^I click the (.*) link in the instructions or preference message$/
     */
    public function iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage($readMoreLink)
    {
        $this->iCanSeeReadMoreLink($readMoreLink);
        $this->ui->clickLink($readMoreLink);
    }

    /**
     * @Then /^I am navigated to the instructions and preferences page$/
     */
    public function iAmNavigatedToTheInstructionsAndPreferencesPage()
    {
        $this->ui->assertPageAddress('/lpa/instructions-preferences');
        $this->ui->assertPageContainsText('Instructions and preferences');
    }

    /**
     * @When /^I am on the instructions and preferences page$/
     */
    public function iAmOnTheInstructionsAndPreferencesPage()
    {
        $this->iAmOnTheDashboardPage();
        $this->iClickTheReadMoreLinkInTheInstructionsOrPreferenceMessage('Read more');
        $this->iAmNavigatedToTheInstructionsAndPreferencesPage();
    }


    /**
     * @When /^I request to add an LPA with the code "([^"]*)" that is for "([^"]*)" "([^"]*)" and I will have an Id of ([^"]*)$/
     */
    public function iRequestToAddAnLPAWithTheCodeThatIsForAndIWillHaveAnIdOf(
        $passcode,
        $firstName,
        $secondName,
        $id
    ) {
        $this->userId = $this->actorId = (int)$id;

        $this->userFirstName = $firstName;
        $this->userSurname = $secondName;

        // API Response for LPA data request, configured with our specified details
        $this->lpaData = [
            'user-lpa-actor-token' => $this->userLpaActorToken,
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
                    'id' => $this->actorId,
                    'uId' => '700000000054',
                    'dob' => '1975-10-05',
                    'salutation' => 'Mr',
                    'firstname' => $this->userFirstName,
                    'middlenames' => null,
                    'surname' => $this->userSurname,
                    'systemStatus' => true,
                    'email' => 'string'
                ],
            ],
            'applicationHasRestrictions' => true,
            'applicationHasGuidance' => false,
            'lpa' => $this->lpa
        ];

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpaData)
                )
            );

        // API call for getting all the users added LPAs
        // to check if they have already added the LPA
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->fillField('passcode', $passcode);
        $this->ui->fillField('reference_number', '700000000054');
        $this->ui->fillField('dob[day]', '05');
        $this->ui->fillField('dob[month]', '10');
        $this->ui->fillField('dob[year]', '1975');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^The correct LPA is found and I can see the correct name which will have a role of "([^"]*)"$/
     */
    public function theCorrectLPAIsFoundAndICanSeeTheCorrectNameWhichWillHaveARoleOf($role)
    {
        // API call for adding an LPA
        $this->apiFixtures->post('/v1/actor-codes/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userLpaActorToken])
                )
            );

        //API call for getting all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([$this->userLpaActorToken => $this->lpaData])
                )
            );

        //API call for getting each LPAs share codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->ui->assertPageAddress('/lpa/check');

        $this->ui->assertPageContainsText('Is this the LPA you want to add?');
        $this->ui->assertPageContainsText(sprintf('Mr %s %s', $this->userFirstName, $this->userSurname));
        $this->ui->assertPageContainsText($role);

        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I navigate to the actor cookies page$/
     */
    public function iNavigateToTheActorCookiesPage()
    {
        $this->ui->clickLink('cookie policy');
    }

    /**
     * @Then /^I am taken to the actor cookies page$/
     */
    public function iAmTakenToTheActorCookiesPage()
    {
        $this->ui->assertPageAddress('/cookies');
        $this->ui->assertPageContainsText('Use a lasting power of attorney service');
    }

    /**
     * @When /^I select the option to sign in to my existing account$/
     */
    public function iSelectTheOptionToSignInToMyExistingAccount()
    {

        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Use a lasting power of attorney');
        $this->ui->fillField('triageEntry', 'yes');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I select the option to create a new account$/
     */
    public function iSelectTheOptionToCreateNewAccount()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->fillField('triageEntry', 'no');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I do not provide any options and continue$/
     */
    public function iDoNotProvideAnyOptionsAndContinue()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Given /^I am not allowed to progress$/
     */
    public function iAmNotAllowedToProgress()
    {
        $this->ui->assertPageAddress('/home');
        $this->ui->assertPageContainsText('Select yes if you have a Use a lasting power of attorney account');
    }

    /**
     * @Then /^I am allowed to create an account$/
     */
    public function iAmAllowedToCreateAnAccount()
    {
        $this->ui->assertPageAddress('/create-account');
        $this->ui->assertPageContainsText('Create an account');
    }

    /**
     * @Given /^I access the account creation page$/
     */
    public function iAccessTheAccountCreationPage()
    {
        $this->ui->visit('/create-account');
        $this->ui->assertPageAddress('/create-account');
    }

    /**
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        // Not needed for this context
    }

    public function elementisOpen(string $searchStr)
    {
        $page = $this->ui->getSession()->getPage();
        $element = $page->find('css', $searchStr);
        $elementHtml = $element->getOuterHtml();
        return str_contains($elementHtml, ' open');
    }

    /**
     * @Given /^I can see that the What I can do link is open$/
     */
    public function iCanSeeThatTheWhatICanDoLinkIsOpen()
    {
        assertTrue($this->elementisOpen('.govuk-details'));
    }

    /**
     * @Given /^I can see that the What I can do link is closed$/
     */
    public function iCanSeeThatTheWhatICanDoLinkIsClosed()
    {
        assertFalse($this->elementisOpen('.govuk-details'));
    }

    /**
     * @Given /^I can see a flash message for the added LPA$/
     */
    public function iCanSeeAFlashMessageForTheAddedLPA()
    {
        $this->ui->assertPageContainsText("You've added Ian Deputy's health and welfare LPA");
    }

    /**
     * @Given /^I am on the change details page$/
     */
    public function iAmOnTheChangeDetailsPage()
    {
        $this->ui->visit('/lpa/change-details');
        $this->ui->assertPageAddress('/lpa/change-details');
    }

    /**
     * @When /^I select to find out more if the donor or an attorney dies$/
     */
    public function iSelectToFindOutMoreIfTheDonorOrAnAttorneyDies()
    {
        $this->ui->clickLink('the donor or an attorney dies');
    }

    /**
     * @Then /^I expect to be on the death notification page$/
     */
    public function iExpectToBeOnTheDeathNotificationPage()
    {
        $this->ui->assertPageAddress('/lpa/death-notification');
    }

    /**
     * @Given /^I am on the death notification page$/
     */
    public function iAmOnTheDeathNotificationPage()
    {
        $this->ui->visit('/lpa/death-notification');
    }

    /**
     * @Then /^I can see banner about existing LPAs$/
     */

    public function iCanSeeBannerAboutExistingLPAs()
    {
        $page = $this->ui->getSession()->getPage();
        $this->ui->assertElementOnPage(".moj-banner__message");
    }

    /**
     * @When /^I cancel the viewer code/
     */
    public function iCancelTheViewerCode()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa->uId,
                            'Added' => '2020-09-16T22:57:12.398570Z',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode' => $this->accessCode,
                            'Cancelled' => '2020-09-16T22:58:43+00:00',
                            'Expires' => '2020-09-16T23:59:59+01:00',
                            'Viewed' => false,
                            'ActorId' => $this->actorId
                        ]
                    ])
                )
            );
    }

    /**
     * @When /^I click to check the viewer code has been cancelled which is now expired/
     */
    public function iClickToCheckTheViewerCodeHasBeenCancelledWhichIsNowExpired()
    {
        $this->ui->clickLink('Check access codes');
    }

    /**
     * @Then /^I can see the accessibility statement for the Use service$/
     */
    public function iCanSeeTheAccessibilityStatementForTheUseService()
    {
        $this->ui->assertPageContainsText('Accessibility statement for Use a lasting power of attorney');
    }

    /**
     * @Given /^I should see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
    {
        $this->ui->assertPageContainsText(
            sprintf(
                "You cancelled the access code for %s: V-XYZ3-21AB-C987",
                $this->organisation
            )
        );
    }

    /**
     * @Given /^I should not see a flash message to confirm the code that I have cancelled$/
     */
    public function iShouldNotSeeAFlashMessageToConfirmTheCodeThatIHaveCancelled()
    {
        $this->ui->assertPageNotContainsText(
            sprintf(
                "You cancelled the access code for %s: V-XYZ3-21AB-C987",
                $this->organisation
            )
        );
    }

    /**
     * @Then /^I can see the name of the organisation that viewed the LPA$/
     */
    public function iCanSeeTheNameOfTheOrganisationThatViewedTheLPA()
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('Natwest');
        $this->ui->assertPageContainsText('Another Organisation');
    }

    /**
     * @When /^I have shared the access code with organisations to view my LPA$/
     */
    public function iHaveSharedTheAccessCodeWithOrganisationsToViewMyLPA()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click to check my access codes that is used to view LPA$/
     */
    public function iClickToCheckMyAccessCodesThatIsUsedToViewLPA()
    {
        $organisation = 'Natwest';

        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid' => $this->lpa->uId,
                            'Added' => '2020-01-01T23:59:59+00:00',
                            'Expires' => '2021-01-01T23:59:59+00:00',
                            'UserLpaActor' => $this->userLpaActorToken,
                            'Organisation' => $this->organisation,
                            'ViewerCode' => $this->accessCode,
                            'Viewed' => [
                                0 => [
                                    'Viewed' => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy' => $organisation
                                ],
                                1 => [
                                    'Viewed' => '2020-10-01T15:27:23.263483Z',
                                    'ViewerCode' => $this->accessCode,
                                    'ViewedBy' => 'Another Organisation'
                                ],
                            ],
                            'ActorId' => $this->actorId
                        ]
                    ])
                )
            );

        $this->ui->clickLink('Check access codes');
    }

    /**
     * @Then /^I can see the code has not been used to view the LPA$/
     */
    public function iCanSeeTheCodeHasNotBeenUsedToViewTheLPA()
    {
        $this->ui->assertPageContainsText('Active codes');
        $this->ui->assertPageContainsText('V - XYZ3 - 21AB - C987');
        $this->ui->assertPageContainsText('LPA Viewed');
        $this->ui->assertPageContainsText('Not viewed');
    }

    /**
     * @Then /^I should be told that I have already added this LPA$/
     */
    public function iShouldBeToldThatIHaveAlreadyAddedThisLPA()
    {
        $this->ui->assertPageContainsText("You've already added this LPA to your account");
    }

    /**
     * @When /^I request to view the LPA that has already been added$/
     */
    public function iRequestToViewTheLPAThatHasAlreadyBeenAdded()
    {
        // API call for get LpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        'user-lpa-actor-token' => $this->userLpaActorToken,
                        'date'                 => 'date',
                        'lpa'                  => $this->lpa,
                        'actor'                => $this->lpaData['actor'],
                    ])
                )
            );

        $this->ui->clickLink('see this LPA');
    }

    /**
     * @Then /^The full LPA is displayed$/
     */
    public function theFullLPAIsDisplayed()
    {
        $this->ui->assertPageAddress('/lpa/view-lpa?=' . $this->userLpaActorToken);
        $this->ui->assertPageContainsText('This LPA is registered');
    }

    /**
     * @Given /^I am on the add an LPA triage page$/
     */
    public function iAmOnTheAddAnLPATriagePage()
    {
        $this->ui->visit('/lpa/add');
        $this->ui->assertPageContainsText('Do you have an activation key to add an LPA?');
    }

    /**
     * @When /^I select (.*) whether I have an activation key$/
     */
    public function iSelectWhetherIHaveAnActivationKey($option)
    {
        $this->ui->fillField('activation_key_triage', $option);
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I will be taken to the appropriate (.*) to add an lpa$/
     */
    public function iWillBeTakenToTheAppropriateToAddAnLpa($page)
    {
        $this->ui->assertPageContainsText($page);
    }

    /**
     * @When /^I select to add an LPA$/
     */
    public function iSelectToAddAnLPA()
    {
        $this->ui->clickLink('Add another LPA');
    }

    /**
     * @When /^I do not select an option for whether I have an activation key$/
     */
    public function iDoNotSelectAnOptionForWhetherIHaveAnActivationKey()
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I will be told that I must select whether I have an activation key$/
     */
    public function iWillBeToldThatIMustSelectWhetherIHaveAnActivationKey()
    {
        $this->ui->assertPageContainsText('Select if you have an activation key to add the LPA');
    }

    /**
     * @Given /^I am on the request an activation key page$/
     */
    public function iAmOnTheRequestAnActivationKeyPage()
    {
        $this->ui->visit('/lpa/add-by-paper');
        $this->ui->assertPageAddress('/lpa/add-by-paper');
    }

    /**
     * @When /^I request an activation key with an invalid lpa reference number format of "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidLpaReferenceNumberFormatOf($referenceNumber)
    {
        $this->ui->fillField('opg_reference_number', $referenceNumber);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key without entering my (.*)$/
     */
    public function iRequestAnActivationKeyWithoutEnteringMy($data)
    {
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with an invalid DOB format of "([^"]*)" "([^"]*)" "([^"]*)"$/
     */
    public function iRequestAnActivationKeyWithAnInvalidDOBFormatOf($day, $month, $year)
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->fillField('first_names', 'Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->fillField('postcode', 'ABC123');
        $this->ui->fillField('dob[day]', $day);
        $this->ui->fillField('dob[month]', $month);
        $this->ui->fillField('dob[year]', $year);
        $this->ui->pressButton('Continue');
    }

    /**
     * @When /^I request an activation key with valid details$/
     */
    public function iRequestAnActivationKeyWithValidDetails()
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->fillField('opg_reference_number', '700000000001');
        $this->ui->fillField('first_names', 'The Attorney');
        $this->ui->fillField('last_name', 'Person');
        $this->ui->fillField('postcode', 'ABC123');
        $this->ui->fillField('dob[day]', '09');
        $this->ui->fillField('dob[month]', '02');
        $this->ui->fillField('dob[year]', '1998');
        $this->ui->pressButton('Continue');
    }

    /**
     * @Then /^I am asked to check my answers before requesting an activation key$/
     */
    public function iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey()
    {
        $this->ui->assertPageAddress('/lpa/check-answers');
        $this->ui->assertPageContainsText('Check your answers');
        $this->ui->assertPageContainsText('700000000001');
        $this->ui->assertPageContainsText('The Attorney Person');
        $this->ui->assertPageContainsText('09/02/1998');
        $this->ui->assertPageContainsText('ABC123');
    }

    /**
     * @Given /^I have requested an activation key with valid details$/
     */
    public function iHaveRequestedAnActivationKeyWithValidDetails()
    {
        $this->iAmOnTheRequestAnActivationKeyPage();
        $this->iRequestAnActivationKeyWithValidDetails();
        $this->iAmAskedToCheckMyAnswersBeforeRequestingAnActivationKey();
    }

    /**
     * @When /^I request to go back and change my answers$/
     */
    public function iRequestToGoBackAndChangeMyAnswers()
    {
        $this->ui->clickLink('Change');
    }

    /**
     * @Then /^I am taken back to previous page where I can see my answers and change them$/
     */
    public function iAmTakenBackToPreviousPageWhereICanSeeMyAnswersAndChangeThem()
    {
        $this->ui->assertPageAddress('/lpa/add-by-paper');
        $this->ui->assertFieldContains('opg_reference_number', '700000000001');
        $this->ui->assertFieldContains('first_names', 'The Attorney');
        $this->ui->assertFieldContains('last_name', 'Person');
        $this->ui->assertFieldContains('dob[day]', '09');
        $this->ui->assertFieldContains('dob[month]', '02');
        $this->ui->assertFieldContains('dob[year]', '1998');
        $this->ui->assertFieldContains('postcode', 'ABC123');
    }

    /**
     * @When /^I say I do not have an activation key$/
     */
    public function iSayIDoNotHaveAnActivationKey()
    {
        $this->ui->fillField('activation_key_triage', 'No');
    }

    /**
     * @When /^I am shown content explaining why I can not use this service$/
     */
    public function iAmShownContentExplainingWhyICannotUseThisService()
    {
        $this->ui->assertPageAddress('/lpa/add');
        $this->ui->assertPageContainsText('If the LPA was registered before this date, you need to use the paper LPA with people and organisations.');
    }
}
