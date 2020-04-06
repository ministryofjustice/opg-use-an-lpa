<?php

declare(strict_types=1);

namespace BehatTest\Context\UI;

use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use BehatTest\Context\ActorContextTrait as ActorContext;
use BehatTest\Context\BaseUiContextTrait;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use DateTime;

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
 */
class AccountContext implements Context
{
    use ActorContext;
    use BaseUiContextTrait;

    /**
     * @Then /^I can no longer access the dashboard page$/
     */
    public function iCanNoLongerAccessTheDashboardPage()
    {
        $this->ui->visit('/lpa/dashboard');

        // a non-logged in attempt will end up at the login page
        $this->ui->assertPageAddress('/login');
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
            'lpa' => $this->lpa
        ];
    }

    /**
     * @Given /^I access the login form$/
     */
    public function iAccessTheLoginForm()
    {
        $this->ui->visit('/login');
        $this->ui->assertPageAddress('/login');
        $this->ui->assertElementContainsText('button[type=submit]', 'Sign in');
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

        $this->ui->iAmOnHomepage();

        $this->ui->clickLink('Sign in');
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
        $this->ui->assertPageContainsText('Email and password combination not recognised');
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
                        'Id'        => $this->userId,
                        'Email'     => $this->userEmail,
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
                    [], json_encode(
                        [
                            'Id'                 => $this->userId,
                            'PasswordResetToken' => '123456'
                        ])));

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
                        ])));
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
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])))
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
        $this->ui->assertPageAddress('/lpa/dashboard');
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
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])));

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

        $this->ui->assertPageContainsText('at least ' . $reason);
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
        $this->ui->visit('/lpa/add-details');
        $this->ui->assertPageAddress('/lpa/add-details');
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        $this->ui->assertPageAddress('/lpa/add-details');

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
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
                    json_encode([])));

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
        $this->ui->assertPageAddress('/lpa/add-details');

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
        $this->ui->assertPageContainsText('We could not find that lasting power of attorney');
    }

    /**
     * @Given /^I request to go back and try again$/
     */
    public function iRequestToGoBackAndTryAgain()
    {
        $this->ui->pressButton('Try again');
        $this->ui->assertPageAddress('/lpa/add-details');
    }

    /**
     * @When /^I request to add an LPA with an invalid passcode format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidPasscodeFormatOf1($passcode)
    {
        $this->ui->assertPageAddress('/lpa/add-details');
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
        $this->ui->assertPageAddress('/lpa/add-details');
        $this->ui->assertPageContainsText($reason);
    }

    /**
     * @When /^I request to add an LPA with an invalid reference number format of "([^"]*)"$/
     */
    public function iRequestToAddAnLPAWithAnInvalidReferenceNumberFormatOf($referenceNo)
    {
        $this->ui->assertPageAddress('/lpa/add-details');
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
        $this->ui->assertPageAddress('/lpa/add-details');
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

        $this->ui->assertPageAddress('/lpa/add-details');
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
     * @Given /^I want to create a new account$/
     */
    public function iWantToCreateANewAccount()
    {
        $this->ui->iAmOnHomepage();
        $this->ui->pressButton('Get started');
        $this->ui->pressButton('Create account');
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
                'Id'              => '123',
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('password', $this->password);
        $this->ui->fillField('password_confirm', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @Then /^I receive unique instructions on how to activate my account$/
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        $this->ui->assertPageAddress('/create-account-success');

        $this->ui->assertPageContainsText('We\'ve emailed a link to ' . $this->email);

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
        // API fixture for reset token check
        $this->apiFixtures->patch('/v1/user-activation')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(
                        [
                            'Id'               => '123',
                            'activation_token' => $this->activationToken,
                        ])));

        $this->ui->visit('/activate-account/' . $this->activationToken);
    }

    /**
     * @Then /^my account is activated$/
     */
    public function myAccountIsActivated()
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
        $this->ui->assertPageAddress('/activate-account/'. $this->activationToken);
        $this->ui->assertPageContainsText('You created the account more than 24 hours ago');
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
                'Email'           => $this->email,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $this->email);
        $this->ui->fillField('password', $this->password);
        $this->ui->fillField('password_confirm', $this->password);
        $this->ui->fillField('terms', 1);
        $this->ui->pressButton('Create account');
    }

    /**
     * @When /^I have provided required information for account creation such as (.*)(.*)(.*)(.*)(.*)$/
     */
    public function iHaveNotProvidedRequiredInformationForAccountCreationSuchAs($email1,$email2,$password1,$password2,$terms)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email1);
        $this->ui->fillField('password', $password1);
        $this->ui->fillField('password_confirm', $password2);

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
     * @When /^Creating account I provide mismatching (.*) (.*)$/
     */
    public function CreatingAccountIProvideMismatching($value1, $value2)
    {
        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $value1);
        $this->ui->fillField('password',  $value1);
        $this->ui->fillField('password_confirm', $value2);

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
                    json_encode([])));

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
                        'actor'                => [],
                    ])));

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
                        'actor'                => [],
                    ])));

        $this->ui->assertPageAddress('lpa/dashboard');
        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' .$this->userLpaActorToken);
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
                            'organisation'=> $this->organisation
                        ]
                    )
                ));

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
                        'actor'                => [],
                    ])));

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
                ));

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
                        'actor' => [],
                    ])));

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
                        'Id'        => $this->userId,
                        'Email'     => $this->userEmail,
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
                        'actor'                => [],
                    ])));

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid'    => $this->lpa->uId,
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Expires'      => '2021-01-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId
                            ]
                        ]
                    )
                ));

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
                        'actor'                => [],
                    ])));

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid'    => $this->lpa->uId,
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Expires'      => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId
                            ]
                        ]
                    )
                ));

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
                        'actor'                => [],
                    ])));

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                            0 => [
                                'SiriusUid'    => $this->lpa->uId,
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Expires'      => '2021-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => $this->accessCode,
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId
                            ],
                            1 => [
                                'SiriusUid'    => $this->lpa->uId,
                                'Added'        => '2020-01-01T23:59:59+00:00',
                                'Expires'      => '2020-02-01T23:59:59+00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                                'ViewerCode'   => "ABC321ABCXYZ",
                                'Viewed'       => false,
                                'ActorId'      => $this->actorId
                            ]
                        ]
                    )
                ));

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
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' .$this->userLpaActorToken);
        $this->ui->assertPageContainsText("Cancel organisation's access");
    }

    /**
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' .$this->userLpaActorToken);

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
                    json_encode([])));

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
                        'actor'                => [],
                    ])));

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode'   => $this->accessCode,
                            'Cancelled'    => '2021-01-02T23:59:59+00:00',
                            'Expires'      => '2021-01-02T23:59:59+00:00',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId
                        ]
                    ])));

        $this->ui->pressButton("Yes, cancel code");
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status (.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledCodeWithStatus($status)
    {
        $this->ui->assertPageAddress('/lpa/access-codes?lpa=' . $this->userLpaActorToken);

        $session = $this->ui->getSession();
        $page = $session->getPage();

        $codeDetails=[];

        $codeSummary = $page->findAll('css', '.govuk-summary-list__row');
        foreach ($codeSummary as $codeItem)
        {
            $codeDetails[] = ($codeItem->find('css', 'dd'))->getText();
        }

        assertEquals($codeDetails[0] ,'V - XYZ3 - 21AB - C987');
        assertEquals($codeDetails[1],'Ian Deputy');
        assertEquals($codeDetails[2],'Not Viewed');
        assertEquals($codeDetails[4], $status);

        if ($codeDetails === null) {
            throw new \Exception( 'Code details not found');
        }
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        $this->iAmOnTheAddAnLPAPage();

        // API call for adding/checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode([])
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
                        'actor'                => [],
                    ])));

        // API call for getShareCodes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([
                        0 => [
                            'SiriusUid'    => $this->lpa->uId,
                            'Added'        => '2020-01-01T23:59:59+00:00',
                            'Organisation' => $this->organisation,
                            'UserLpaActor' => $this->userLpaActorToken,
                            'ViewerCode'   => $this->accessCode,
                            'Expires'      => '2021-01-05T23:59:59+00:00',
                            'Viewed'       => false,
                            'ActorId'      => $this->actorId,
                        ]
                    ])));

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
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        $this->ui->assertPageContainsText('We could not find that lasting power of attorney');
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
            'SiriusUid'    => $this->lpa->uId,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
            'ViewerCode'   => $this->accessCode,
            'Expires'      => $code1Expiry,
            'Viewed'       => false,
            'ActorId'      => $this->actorId,
        ];

        $code2 = [
            'SiriusUid'    => $this->lpa->uId,
            'Added'        => '2020-01-01T23:59:59+00:00',
            'Organisation' => $this->organisation,
            'UserLpaActor' => $this->userLpaActorToken,
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
                    ])));

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
                    json_encode([])));

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
                        'actor'                => [],
                    ])));

        // API call to get access codes
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken . '/codes')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])));

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
                        'actor'                => [],
                    ])));

        $this->ui->clickLink('Give an organisation access');
        $this->ui->assertPageAddress('lpa/code-make?lpa=' .$this->userLpaActorToken);
        $this->ui->assertPageContainsText('Which organisation do you want to give access to');

    }

    /**
     * @When /^I ask to change my password$/
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
     * @Given /^I provide my new password$/
     */
    public function iProvideMyNewPassword()
    {
        $newPassword = 'S0meS0rt0fPassw0rd';

        $this->ui->fillField('new_password', $newPassword);
        $this->ui->fillField('new_password_confirm', $newPassword);

        $this->ui->pressButton('Change password');
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        // Not needed for one this context
    }

    /**
     * @Given /^I cannot enter my current password$/
     */
    public function iCannotEnterMyCurrentPassword()
    {
        $this->ui->fillField('current_password', 'NotMyPassword1');

        $this->iProvideMyNewPassword();
    }

    /**
     * @Then /^The user can request a password reset and get an email$/
     */
    public function theUserCanRequestAPasswordResetAndGetAnEmail()
    {
        // Not needed for one this context
    }

    /**
     * @Given /^I choose a new password of "([^"]*)"$/
     */
    public function iChooseANewPasswordOf($password)
    {
        $this->ui->assertPageAddress('/change-password');

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

        $this->ui->assertPageContainsText('at least ' . $reason);
    }



    /**
     * @When /^I enter correct email with (.*) and (.*) below$/
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
                        'Id'        => $this->userId,
                        'Email'     => $email_format,
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
     * @Then /^I should see relevant (.*) message$/
     */
    public function iShouldSeeRelevantErrorMessage($error)
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText($error);
    }

    /**
     * @When /^I enter incorrect email with (.*) and (.*) below$/
     */
    public function iEnterInCorrectEmailWithEmailFormatAndPasswordBelow($emailFormat, $password)
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
    public function iAskForMyPasswordToBeResetWithBelowCorrectEmailAndConfirmationEmailDetails($email,$email_confirmation)
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [], json_encode(
                    [
                        'Id'                 => $this->userId,
                        'PasswordResetToken' => '123456'
                    ])));

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
        $this->ui->assertPageContainsText('emailed a link to ' .strtolower($email));
    }

    /**
     * @When /^I ask for my password to be reset with below incorrect (.*) and (.*) details$/
     */
    public function iAskForMyPasswordToBeResetWithBelowInCorrectEmailAndConfirmationEmailDetails($email,$email_confirmation)
    {
        $this->ui->assertPageAddress('/forgot-password');

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_FORBIDDEN,
                    [], json_encode([])));

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
    public function anAccountIsCreatedUsingEmail1Password1Password2Terms($email1,$password1,$password2,$terms)
    {
        $this->activationToken = 'activate1234567890';

        $this->ui->assertPageAddress('/create-account');

        // API call for password reset request
        $this->apiFixtures->post('/v1/user')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'              => '123',
                'Email'           => $email1,
                'ActivationToken' => $this->activationToken,
            ])));

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])));

        $this->ui->fillField('email', $email1);
        $this->ui->fillField('password', $password1);
        $this->ui->fillField('password_confirm', $password2);
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
     * @Then /^I can see the actor terms of use$/
     */
    public function iCanSeeTheActorTermsOfUse()
    {
        $this->ui->assertPageAddress('/lpa/terms-of-use');
        $this->ui->assertPageContainsText('Terms of use');
        $this->ui->assertPageContainsText('The service is for donors and attorneys on an LPA.');
    }

    /**
     * @Given /^I am on the actor terms of use page$/
     */
    public function iAmOnTheActorTermsOfUsePage()
    {
        $this->ui->visit('/lpa/terms-of-use');
        $this->ui->assertPageAddress('/lpa/terms-of-use');
    }

    /**
     * @When /^I request to go back to the create account page$/
     */
    public function iRequestToGoBackToTheCreateAccountPage()
    {
        $this->ui->clickLink('Back');
    }

    /**
     * @Then /^I am taken back to the create account page$/
     */
    public function iAmTakenBackToTheCreateAccountPage()
    {
        $this->ui->assertPageAddress('/create-account');
    }

    /**
     * @Given /^I am on the index page$/
     */
    public function iAmOnTheIndexPage()
    {
        $this->ui->visit('/');
        $this->ui->assertPageContainsText('Use a lasting power of attorney');
    }

    /**
     * @When /^I request to get started with the service$/
     */
    public function iRequestToGetStartedWithTheService()
    {
        $this->ui->clickLink('Get started');
    }

    /**
     * @Then /^I am taken to the get started page$/
     */
    public function iAmTakenToTheGetStartedPage()
    {
        $this->ui->assertPageAddress('/start');
        $this->ui->assertPageContainsText('Get started');
    }

    /**
     * @When /^I select the option to sign in to my existing account$/
     */
    public function iSelectTheOptionToSignInToMyExistingAccount()
    {
        $this->ui->clickLink('Sign in to your existing account');
    }

    /**
     * @Given /^I am on the get started page$/
     */
    public function iAmOnTheGetStartedPage()
    {
        $this->ui->visit('/start');
        $this->ui->assertPageContainsText('Get started');
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
     * @Then /^I am taken to the login page$/
     */
    public function iAmTakenToTheLoginPage()
    {
        $this->ui->assertPageAddress('/login');
        $this->ui->assertPageContainsText('Sign in to your Use a lasting power of attorney account');
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
                    json_encode([])));

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
                        'actor'                => [],
                    ])));

        $this->ui->clickLink('Give an organisation access');
    }

    /**
     * @Then /^I should see relevant (.*) of organisations$/
     */
    public function iShouldSeeRelevantOfOrganisations($orgExamples)
    {
        $this->ui->assertPageAddress('lpa/code-make?lpa=' .$this->userLpaActorToken);
        $this->ui->assertPageContainsText($orgExamples);
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
        $this->ui->assertPageAddress('lpa/view-lpa?lpa=' .$this->userLpaActorToken);
        $this->ui->clickLink($link);
    }

    /**
     * @Then /^I am taken to the change details page$/
     */
    public function iAmTakenToTheChangeDetailsPage()
    {
        $this->ui->assertPageAddress('lpa/change-details?lpa=' .$this->userLpaActorToken);
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
                    json_encode([])));

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
     * @Given /^I am logged out of the service and taken to the index page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage()
    {
        $this->ui->assertPageAddress('/');
    }

}
