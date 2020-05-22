<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use App\Exception\GoneException;
use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Common\Exception\ApiException;
use DateTime;
use DateInterval;
use DateTimeZone;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Acceptance
 *
 * @property $actorId
 * @property $userAccountId
 * @property $userAccountEmail
 * @property $userAccountPassword
 * @property $userAccountCreateData
 * @property $passwordResetData
 * @property $oneTimeCode
 * @property $lpaUid
 * @property $userDob
 * @property $userId
 * @property $lpa
 * @property $userLpaActorToken
 * @property $organisation
 * @property $accessCode
 * @property $newEmail
 * @property $userEmailResetToken
 */
class AccountContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'));

        $this->oneTimeCode = 'XYUPHWQRECHV';
        $this->lpaUid = '700000000054';
        $this->userDob = '1975-10-05';
        $this->actorId = 9;
        $this->userId = '111222333444';
        $this->userLpaActorToken = '111222333444';

    }

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userAccountId = '123456789';
        $this->userAccountEmail = 'test@example.com';
        $this->userAccountPassword = 'pa33w0rd';
    }

    /**
     * @Given /^I access the login form$/
     */
    public function iAccessTheLoginForm()
    {
        // Not needed in this context
    }

    /**
     * @When /^I enter correct credentials$/
     */
    public function iEnterCorrectCredentials()
    {
        // Not needed in this context
    }

    /**
     * @Given I am currently signed in
     * @Then /^I am signed in$/
     */
    public function iAmCurrentlySignedIn()
    {
        $this->userAccountPassword = 'pa33w0rd';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'       => $this->userAccountId,
                    'Email'    => $this->userAccountEmail,
                    'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin'=> null
                ])
            ]
        ]));

        // ActorUsers::recordSuccessfulLogin
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->userAccountId,
                    'LastLogin' => null
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->userAccountEmail,
            'password' => $this->userAccountPassword
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->userAccountId, $response['Id']);
    }

    /**
     * @When /^I enter incorrect login password$/
     */
    public function iEnterIncorrectLoginPassword()
    {
        // Not needed in this context
    }

    /**
     * @When /^I enter incorrect login email$/
     */
    public function iEnterIncorrectLoginEmail()
    {
        // Not needed in this context
    }

    /**
     * @Then /^my account cannot be found$/
     */
    public function myAccountCannotBeFound()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/auth', [
            'email'    => 'incorrect@email.com',
            'password' => $this->userAccountPassword
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    /**
     * @Then /^I am told my credentials are incorrect$/
     */
    public function iAmToldMyCredentialsAreIncorrect()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'       => $this->userAccountId,
                    'Email'    => $this->userAccountEmail,
                    'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin'=> null
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->userAccountEmail,
            'password' => '1nc0rr3ctPa33w0rd'
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
    }

    /**
     * @Given /^I have not activated my account$/
     */
    public function iHaveNotActivatedMyAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told my account has not been activated$/
     */
    public function iAmToldMyAccountHasNotBeenActivated()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'              => $this->userAccountId,
                    'Email'           => $this->userAccountEmail,
                    'Password'        => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin'       => null,
                    'ActivationToken' => 'a12b3c4d5e'
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->userAccountEmail,
            'password' => $this->userAccountPassword
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_UNAUTHORIZED);
    }

    /**
     * @Given I have forgotten my password
     */
    public function iHaveForgottenMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When I ask for my password to be reset
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->passwordResetData = [
            'Id'                  => $this->userAccountId,
            'PasswordResetToken'  => 'AAAABBBBCCCC'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::requestPasswordReset
        $this->awsFixtures->append(new Result([
            'Attributes' => $this->marshalAwsResultData([
                'Id'                  => $this->userAccountId,
                'PasswordResetToken'  => $this->passwordResetData['PasswordResetToken'],
                'PasswordResetExpiry' => time() + (60 * 60 * 24) // 24 hours in the future
            ])
        ]));

        $this->apiPatch('/v1/request-password-reset', ['email' => $this->userAccountEmail], []);
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->userAccountId, $response['Id']);
        assertEquals($this->passwordResetData['PasswordResetToken'], $response['PasswordResetToken']);
    }

    /**
     * @Given I have asked for my password to be reset
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->passwordResetData = [
            'Id'                  => $this->userAccountId,
            'PasswordResetToken'  => 'AAAABBBBCCCC',
            'PasswordResetExpiry' => time() + (60 * 60 * 12) // 12 hours in the future
        ];
    }

    /**
     * @When I follow my unique instructions on how to reset my password
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->userAccountId,
                'Email'               => $this->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry']
            ])
        ]));

        $this->apiGet('/v1/can-password-reset?token=' . $this->passwordResetData['PasswordResetToken'], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->userAccountId, $response['Id']);
    }

    /**
     * @When I choose a new password
     */
    public function iChooseANewPassword()
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->userAccountId,
                'Email'               => $this->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry']
            ])
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/complete-password-reset', [
            'token'    => $this->passwordResetData['PasswordResetToken'],
            'password' => 'newPassw0rd'
        ], []);
    }

    /**
     * @Then my password has been associated with my user account
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertInternalType('array', $response); // empty array response
    }

    /**
     * @When I follow my unique expired instructions on how to reset my password
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        // expire the password reset token
        $this->passwordResetData['PasswordResetExpiry'] = time() - (60 * 60 * 12); // 12 hours in the past

        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->userAccountId,
                'Email'               => $this->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry']
            ])
        ]));

        $this->apiGet('/v1/can-password-reset?token=' . $this->passwordResetData['PasswordResetToken'], []);
    }

    /**
     * @Then I am told that my instructions have expired
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    /**
     * @Then I am unable to continue to reset my password
     *
     * Typically this endpoint wouldn't be called as we stop at the previous step, in this
     * case though we're using it to test that the endpoint still denies an expired token
     * when directly calling the reset
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->userAccountId,
                'Email'               => $this->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry']
            ])
        ]));

        $this->apiPatch('/v1/complete-password-reset', [
            'token'    => $this->passwordResetData['PasswordResetToken'],
            'password' => 'newPassw0rd'
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        // Not used in this context
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        // ActorCodes::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid' => $this->lpaUid,
                'Active' => true,
                'Expires' => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->oneTimeCode,
                'ActorLpaId' => $this->actorId,
            ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost('/v1/actor-codes/summary', [
            'actor-code' => $this->oneTimeCode,
            'uid' => $this->lpaUid,
            'dob' => $this->userDob
        ], [
            'user-token' => $this->userLpaActorToken
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->lpaUid, $response['lpa']['uId']);
    }

    /**
     * @Given I am not a user of the lpa application
     */
    public function iAmNotaUserOftheLpaApplication()
    {
        // Not needed for this context
    }

    /**
     * @Given I want to create a new account
     */
    public function iWantTocreateANewAccount()
    {
        // Not needed for this context
    }

    /**
     * @When I create an account
     */
    public function iCreateAnAccount()
    {
        $this->userAccountCreateData = [
            'Id'                  => 1,
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => []
        ]));

        // ActorUsers::checkIfEmailResetRequested
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Email' => $this->userAccountCreateData['Email'],
                'ActivationToken' => $this->userAccountCreateData['ActivationToken']
            ])
        ]));

        $this->apiPost('/v1/user', [
            'email' => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password']
        ], []);

        assertEquals($this->userAccountCreateData['Email'], $this->getResponseAsJson()['Email']);
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // not needed for this context
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        $this->userLpaActorToken = '13579';
        $now = (new DateTime)->format('Y-m-d\TH:i:s.u\Z');

        // ActorCodes::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid' => $this->lpaUid,
                'Active'    => true,
                'Expires'   => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->oneTimeCode,
                'ActorLpaId'=> $this->actorId,
            ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // UserLpaActorMap::create
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'        => $this->userLpaActorToken,
                'UserId'    => $this->userAccountId,
                'SiriusUid' => $this->lpaUid,
                'ActorId'   => $this->actorId,
                'Added'     => $now,
            ])
        ]));

        // ActorCodes::flagCodeAsUsed
        $this->awsFixtures->append(new Result([]));

        $this->apiPost('/v1/actor-codes/confirm', [
            'actor-code' => $this->oneTimeCode,
            'uid'        => $this->lpaUid,
            'dob'        => $this->userDob
        ], [
            'user-token' => $this->userLpaActorToken
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CREATED);

        $response = $this->getResponseAsJson();
        assertNotNull($response['user-lpa-actor-token']);
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        // ActorCodes::get
        $this->awsFixtures->append(new Result([]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->apiPost('/v1/actor-codes/summary', [
            'actor-code' => $this->oneTimeCode,
            'uid'        => $this->lpaUid,
            'dob'        => $this->userDob
        ], [
            'user-token' => $this->userLpaActorToken
        ]);
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);

        $response = $this->getResponseAsJson();

        assertEmpty($response['data']);
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
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([]));

        // API call for finding all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $this->apiGet('/v1/lpas', [
            'user-token' => $this->userLpaActorToken
        ]);
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
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }

    /**
     * @When I create an account using duplicate details
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->userAccountCreateData = [
            'Id'                  => 1,
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'AccountActivationToken'  => $this->userAccountCreateData['ActivationToken'] ,
                    'Email' => $this->userAccountCreateData['Email'],
                    'Password' => $this->userAccountCreateData['Password']
                ])
            ]
        ]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Email' => $this->userAccountCreateData['Email'],
                'ActivationToken' => $this->userAccountCreateData['ActivationToken']
            ])
        ]));

        $this->apiPost('/v1/user', [
            'email' => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password']
        ], []);
        assertContains(
            'User already exists with email address ' . $this->userAccountCreateData['Email'],
            $this->getResponseAsJson()
        );

    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->userAccountCreateData = [
            'Id'                  => '11',
            'ActivationToken'     => 'activate1234567890',
            'ActivationTokenExpiry' => time() + (60 * 60 * 12) // 12 hours in the future
        ];
    }

    /**
     * @Then I am informed about an existing account
     */
    public function iAmInformedAboutAnExistingAccount()
    {
        assertEquals('activate1234567890', $this->userAccountCreateData['ActivationToken']);
    }

    /**
     * @Then I receive unique instructions on how to activate my account
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        // Not used in this context
    }

    /**
     * @When I follow the instructions on how to activate my account
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'     => $this->userAccountCreateData['Id']
                ])
            ]
        ]));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => $this->userAccountCreateData['Id']
            ])
        ]));

        $this->apiPatch('/v1/user-activation', ['activation_token' => $this->userAccountCreateData['ActivationToken']], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->userAccountCreateData['Id'], $response['Id']);
    }

    /**
     * @When I follow my instructions on how to activate my account after 24 hours
     */
    public function iFollowMyInstructionsOnHowToActivateMyAccountAfter24Hours()
    {
        // ActorUsers::activate
        $this->awsFixtures->append(new Result(
            [
                'Items' => []
            ]));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => '1'
            ])
        ]));

        $this->apiPatch('/v1/user-activation', ['activation_token' => $this->userAccountCreateData['ActivationToken']], []);

        $response = $this->getResponseAsJson();
        assertContains("User not found for token", $response);
    }

    /**
     * @Then I am told my unique instructions to activate my account have expired
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        // Not used in this context
    }

    /**
     * @Then my account is activated
     */
    public function myAccountIsActivated()
    {
        //Not needed in this context
    }

    /**
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveAddedAnLPAToMyAccount()
    {
        $this->iHaveBeenGivenAccessToUseAnLPAViaCredentials();
        $this->iAmOnTheAddAnLPAPage();
        $this->iRequestToAddAnLPAWithValidDetails();
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
     * @When /^I view my dashboard$/
     */
    public function iViewMyDashboard()
    {
        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(
            new Result([
                'Items' => [
                    $this->marshalAwsResultData([
                        'SiriusUid' => $this->lpaUid,
                        'Added'     => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                        'Id'        => $this->userLpaActorToken,
                        'ActorId'   => $this->actorId,
                        'UserId'    => $this->userAccountId
                    ])
                ]
           ])
        );

        // LpaRepository::get
        $request = $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken
            ]
        );

        $this->setLastRequest($request);
    }

    /**
     * @When /^I request to view an LPA which status is "([^"]*)"$/
     */
    public function iRequestToViewAnLPAWhichStatusIs($status)
    {
        $this->lpa->status = $status;

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userAccountId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userAccountId
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($this->userLpaActorToken, $response['user-lpa-actor-token']);
        assertEquals($this->lpaUid, $response['lpa']['uId']);
        assertEquals($status, $response['lpa']['status']);
    }

    /**
     * @Then /^The full LPA is displayed with the correct (.*)$/
     */
    public function theFullLPAIsDisplayedWithTheCorrect($message)
    {
        // Not needed for this context
    }

    /**
     * @When /^I request to give an organisation access to one of my LPAs$/
     */
    public function iRequestToGiveAnOrganisationAccessToOneOfMyLPAs()
    {
        $this->organisation = "TestOrg";
        $this->accessCode = "XYZ321ABC987";

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::add
        $this->awsFixtures->append(new Result());

        // ViewerCodeService::createShareCode
        $this->apiPost('/v1/lpas/' . $this->userLpaActorToken . '/codes', ['organisation' => $this->organisation],
            [
                'user-token' => $this->userId
            ]
        );
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        $codeExpiry = (new DateTime($response['expires']))->format('Y-m-d');
        $in30Days = (new DateTime('23:59:59 +30 days', new DateTimeZone('Europe/London')))->format('Y-m-d');

        assertArrayHasKey('code', $response);
        assertNotNull($response['code']);
        assertEquals($codeExpiry, $in30Days);
        assertEquals($response['organisation'], $this->organisation);
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
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // API call to get lpa
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['id'], $this->actorId);
        assertEquals($response['actor']['details']['uId'], $this->lpaUid);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => '2021-01-05 12:34:56',
                    'Expires'          => '2022-01-05 12:34:56',
                    'UserLpaActor'     => $this->userLpaActorToken,
                    'Organisation'     => $this->organisation,
                    'ViewerCode'       => $this->accessCode
                ])
            ]
        ]));

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
        ]));

        // API call to get access codes
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2021-01-05 12:34:56');

        //check if the code expiry date is in the past
        assertGreaterThan(strtotime((new DateTime('now'))->format('Y-m-d')), strtotime($response[0]['Expires']));
    }

    /**
     * @Then /^I can see all of my access codes and their details$/
     */
    public function iCanSeeAllOfMyAccessCodesAndTheirDetails()
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
     * @Given /^I am on the create viewer code page$/
     */
    public function iAmOnTheCreateViewerCodePage()
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
     * @When /^I cancel the organisation access code/
     */
    public function iCancelTheOrganisationAccessCode()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid,)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // API call to get lpa
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['id'], $this->actorId);
        assertEquals($response['actor']['details']['uId'], $this->lpaUid);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => '2021-01-05 12:34:56',
                    'Expires'          => '2022-01-05 12:34:56',
                    'UserLpaActor'     => $this->userLpaActorToken,
                    'Organisation'     => $this->organisation,
                    'ViewerCode'       => $this->accessCode
                ])
            ]
        ]));

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
        ]));

        // API call to get access codes
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2021-01-05 12:34:56');
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertArrayHasKey('Cancelled', $response);
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        $shareCode = [
            'SiriusUid'        => $this->lpaUid,
            'Added'            => '2021-01-05 12:34:56',
            'Expires'          => '2022-01-05 12:34:56',
            'Cancelled'        => '2022-01-05 12:34:56',
            'UserLpaActor'     => $this->userLpaActorToken,
            'Organisation'     => $this->organisation,
            'ViewerCode'       => $this->accessCode
        ];

        //viewerCodesRepository::get
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    0 => [
                        'SiriusUid' => $this->lpaUid,
                        'Added' => '2021-01-05 12:34:56',
                        'Expires' => '2022-01-05 12:34:56',
                        'Cancelled' => '2022-01-05 12:34:56',
                        'UserLpaActor' => $this->userLpaActorToken,
                        'Organisation' => $this->organisation,
                        'ViewerCode' => $this->accessCode
                    ]
                ])
            ]
        ]));

        // ViewerCodes::cancel
        $this->awsFixtures->append(new Result());

        // ViewerCodeService::cancelShareCode
        $this->apiPut('/v1/lpas/' . $this->userLpaActorToken . '/codes', ['code' => $shareCode],
            [
                'user-token' => $this->userAccountId
            ]
        );

    }

    /**
     * @When /^One of the generated access code has expired$/
     */
    public function oneOfTheGeneratedAccessCodeHasExpired()
    {
        // Not needed for this context
    }

    /**
     * @When /^I attempt to add the same LPA again$/
     */
    public function iAttemptToAddTheSameLPAAgain()
    {
        // ActorCodes::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid' => $this->lpaUid,
                'Active' => false,
                'Expires' => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->oneTimeCode,
                'ActorLpaId' => $this->actorId,
            ])
        ]));

        // LpaService::getLpaById
        $this->apiPost('/v1/actor-codes/summary',
            [
                'actor-code' => $this->oneTimeCode,
                'uid'        => $this->lpaUid,
                'dob'        => $this->userDob,
            ],
            [
                'user-token' => $this->userAccountId
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheViewerCodeWithStatus()
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
        // Not needed for this context
    }

    /**
     * @When /^I click to check my access code now expired/
     */
    public function iClickToCheckMyAccessCodeNowExpired()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // API call to get lpa
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey('date', $response);
        assertArrayHasKey('actor', $response);
        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['id'], $this->actorId);
        assertEquals($response['actor']['details']['uId'], $this->lpaUid);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => '2019-01-05 12:34:56',
                    'Expires'          => '2019-12-05',
                    'UserLpaActor'     => $this->userLpaActorToken,
                    'Organisation'     => $this->organisation,
                    'ViewerCode'       => $this->accessCode
                ])
            ]
        ]));

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
        ]));

        // API call to get access codes
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
        $response = $this->getResponseAsJson();

        assertArrayHasKey('ViewerCode', $response[0]);
        assertArrayHasKey('Expires', $response[0]);
        assertEquals($response[0]['Organisation'], $this->organisation);
        assertEquals($response[0]['SiriusUid'], $this->lpaUid);
        assertEquals($response[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($response[0]['Added'], '2019-01-05 12:34:56');
        assertNotEquals($response[0]['Expires'], (new DateTime('now'))->format('Y-m-d'));
        //check if the code expiry date is in the past
        assertGreaterThan(strtotime($response[0]['Expires']),strtotime((new DateTime('now'))->format('Y-m-d')));
    }

    /**
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iHaveCreatedAnAccessCode();
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
     * @When /^I check my access codes$/
     */
    public function iCheckMyAccessCodes()
    {
        // Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // API call to get lpa
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken,
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($response['user-lpa-actor-token'], $this->userLpaActorToken);
        assertEquals($response['lpa']['uId'], $this->lpa->uId);
        assertEquals($response['actor']['details']['id'], $this->actorId);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([]));


        // API call to get access codes
        $this->apiGet('/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }

    /**
     * @Then /^I can see that my LPA has (.*) with expiry dates (.*) (.*)$/
     */
    public function iCanSeeThatMyLPAHasWithExpiryDates($noActiveCodes, $code1Expiry, $code2Expiry)
    {
        $code1 = [
            'SiriusUid'        => $this->lpaUid,
            'Added'            => '2020-01-01T00:00:00Z',
            'Expires'          => $code1Expiry,
            'UserLpaActor'     => $this->userLpaActorToken,
            'Organisation'     => $this->organisation,
            'ViewerCode'       => $this->accessCode
        ];

        $code2 = [
            'SiriusUid'        => $this->lpaUid,
            'Added'            => '2020-01-01T00:00:00Z',
            'Expires'          => $code2Expiry,
            'UserLpaActor'     => $this->userLpaActorToken,
            'Organisation'     => $this->organisation,
            'ViewerCode'       => $this->accessCode
        ];

        // LpaService:getLpas

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
            ]
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid,)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey($this->userLpaActorToken, $response);
        assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken );
        assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId );
        assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid );

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodesRepository::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData($code1),
                $this->marshalAwsResultData($code2)
            ]
        ]));

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // This response is duplicated for the 2nd code

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
        ]));

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        // Loop for asserting on both the 2 codes returned
        for ($i=0; $i < 2; $i++) {
            assertCount(2, $response);
            assertEquals($response[$i]['SiriusUid'], $this->lpaUid);
            assertEquals($response[$i]['UserLpaActor'], $this->userLpaActorToken);
            assertEquals($response[$i]['Organisation'], $this->organisation);
            assertEquals($response[$i]['ViewerCode'], $this->accessCode);
            assertEquals($response[$i]['ActorId'], $this->actorId);

            if ($i == 0) {
                assertEquals($response[$i]['Expires'], $code1Expiry);
            } else {
                assertEquals($response[$i]['Expires'], $code2Expiry);
            }
        }
    }

    /**
     * @Then /^I can see that no organisations have access to my LPA$/
     */
    public function iCanSeeThatNoOrganisationsHaveAccessToMyLPA()
    {
        // LpaService:getLpas

        // UserLpaActorMap::getUsersLpas
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                    'Id'               => $this->userLpaActorToken,
                    'ActorId'          => $this->actorId,
                    'UserId'           => $this->userId
                ])
            ]
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid,)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)));

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas',
            [
                'user-token' => $this->userLpaActorToken
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertArrayHasKey($this->userLpaActorToken, $response);
        assertEquals($response[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken );
        assertEquals($response[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId );
        assertEquals($response[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid );

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodesRepository::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result());

        // LpaService::getLpas
        $this->apiGet(
            '/v1/lpas/' . $this->userLpaActorToken . '/codes',
            [
                'user-token' => $this->userId
            ]
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }

    /**
     * @Given /^I am on the user dashboard page$/
     */
    public function iAmOnTheUserDashboardPage()
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
        $newPassword = 'Successful-Raid-on-the-Cooki3s!';

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'       => $this->userAccountId,
            'password'      => $this->userAccountPassword,
            'new-password'  => $newPassword,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I cannot enter my current password$/
     */
    public function iCannotEnterMyCurrentPassword()
    {
        $failedPassword = 'S0meS0rt0fPassw0rd';
        $newPassword = 'Successful-Raid-on-the-Cooki3s!';

        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'       => $this->userAccountId,
            'password'      => $failedPassword,
            'new-password'  => $newPassword,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
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
        // Not needed in this context
    }

    /**
     * @When /^I request to delete my account$/
     */
    public function iRequestToDeleteMyAccount()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
    {
        // Not needed in this context
    }

    /**
     * @Then /^My account is deleted$/
     */
    public function myAccountIsDeleted()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::delete
        $this->awsFixtures->append(new Result([]));

        $this->apiDelete('/v1/delete-account/' . $this->userAccountId);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
    }

    /**
     * @Given /^I am logged out of the service and taken to the index page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage()
    {
        $this->newEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => 'inc0rr3cT'
        ], []);
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
    }

    /**
     * @When /^I request to change my email to an email address that is taken by another user on the service$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatIsTakenByAnotherUserOnTheService()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->userAccountEmail,
                    'Password' => $this->userAccountPassword
                ])
            ]
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->userAccountPassword
        ], []);
    }

    /**
     * @Then /^I should be told that I could not change my email as their was a problem with the request$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailAsTheirWasAProblemWithTheRequest()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CONFLICT);
    }

    /**
     * @When /^I request to change my email to an email address that another user has requested to change their email to but their token has not expired$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatAnotherUserHasRequestedToChangeTheirEmailToButTheirTokenHasNotExpired()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::checkIfEmailResetRequested
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetExpiry' => 1590156718,
                    'Email'            => 'another@user.com',
                    'LastLogin'        => null,
                    'Id'               => 'aaaaaa1111111',
                    'NewEmail'         => $this->newEmail,
                    'EmailResetToken'  => 't0ken12345',
                    'Password'         => 'otherU53rsPa55w0rd'
                ])
            ]
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->userAccountPassword
        ], []);
    }

    /**
     * @When /^I request to change my email to an email address that another user has requested to change their email to but their token has expired$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThatAnotherUserHasRequestedToChangeTheirEmailToButTheirTokenHasExpired()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // Expired
        $otherUsersTokenExpiry = time() - (60);

        // ActorUsers::checkIfEmailResetRequested
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetExpiry' => $otherUsersTokenExpiry,
                    'Email'            => 'another@user.com',
                    'LastLogin'        => null,
                    'Id'               => 'aaaaaa1111111',
                    'NewEmail'         => $this->newEmail,
                    'EmailResetToken'  => 't0ken12345',
                    'Password'         => 'otherU53rsPa55w0rd'
                ])
            ]
        ]));

        // ActorUsers::recordChangeEmailRequest
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'EmailResetExpiry' => 1589965609,
                'Email'            => $this->userAccountEmail,
                'LastLogin'        => null,
                'Id'               => $this->userAccountId,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
                'Password'         => $this->userAccountPassword
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->userAccountPassword
        ]);
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be logged out and told that my request was successful$/
     */
    public function iShouldBeLoggedOutAndToldThatMyRequestWasSuccessful()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($this->userAccountId, $response['Id']);
        assertEquals($this->userAccountEmail, $response['Email']);
        assertEquals($this->newEmail, $response['NewEmail']);
        assertEquals($this->userAccountPassword, $response['Password']);
        assertEquals($this->userEmailResetToken, $response['EmailResetToken']);
        assertArrayHasKey('EmailResetExpiry', $response);
    }

    /**
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress()
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::checkIfEmailResetRequested
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::recordChangeEmailRequest
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'EmailResetExpiry' => 1589965609,
                'Email'            => $this->userAccountEmail,
                'LastLogin'        => null,
                'Id'               => $this->userAccountId,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
                'Password'         => $this->userAccountPassword
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->userAccountPassword
        ]);
    }

    /**
     * @Given /^I have requested to change my email address$/
     */
    public function iHaveRequestedToChangeMyEmailAddress()
    {
        $this->userEmailResetToken = '12345abcde';
        $this->newEmail = 'newEmail@test.com';
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress()
    {
        // canResetEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken'  => $this->userEmailResetToken
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->userAccountId,
                'Email'            => $this->userAccountEmail,
                'Password'         => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                'EmailResetExpiry' => (time() + (60 * 60)),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken
            ])
        ]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($this->userAccountId, $response['Id']);

        //completeChangeEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken'  => $this->userEmailResetToken
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Email'    => $this->userAccountEmail,
                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                'EmailResetExpiry' => (time() + (60 * 60)),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken
            ])
        ]));

        // ActorUsers::changeEmail
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/complete-change-email', [
            'reset_token' => $this->userEmailResetToken,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals([], $response);
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
        // Not needed for this context
    }

    /**
     * @When /^I click the link to verify my new email address after my token has expired$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired()
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken'  => $this->userEmailResetToken
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->userAccountId,
                'Email'            => $this->userAccountEmail,
                'Password'         => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                'EmailResetExpiry' => (time() - (60 * 60)),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken
            ])
        ]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged()
    {
        // Not needed for this context
    }

    /**
     * @When /^I click an old link to verify my new email address containing a token that no longer exists$/
     */
    public function iClickAnOldLinkToVerifyMyNewEmailAddressContainingATokenThatNoLongerExists()
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $this->userAccountCreateData = [
            'Id'                  => 1,
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => []
        ]));

        // ActorUsers::checkIfEmailResetRequested
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'               => $this->userAccountId,
                    'Email'            => 'other@user.co.uk',
                    'Password'         => password_hash('passW0rd', PASSWORD_DEFAULT),
                    'EmailResetExpiry' => (time() + (60 * 60)),
                    'LastLogin'        => null,
                    'NewEmail'         => 'test@test.com',
                    'EmailResetToken'  => 'abc1234567890'
                ])]
        ]));

        $this->apiPost('/v1/user', [
            'email' => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password']
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CONFLICT);
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        // Not needed for this context
    }
}
