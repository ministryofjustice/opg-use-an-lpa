<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Behat\Behat\Tester\Exception\PendingException;
use BehatTest\Context\SetupEnv;
use ClassesWithParents\D;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Acceptance
 *
 * @property $userAccountId
 * @property $userId
 * @property $actorId
 * @property $userAccountEmail
 * @property $userAccountPassword
 * @property $passwordResetData
 * @property $passcode
 * @property $referenceNo
 * @property $userDob
 * @property $lpa
 * @property $userToken
 * @property $actorAccountCreateData
 */
class AccountContext extends BaseAcceptanceContext
{
    use SetupEnv;

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '/../../../../../../opg-use-an-lpa/service-front/app/test/CommonTest/Service/Lpa/fixtures/full_example.json'));

        $this->passcode = 'XYUPHWQRECHV';
        $this->referenceNo = '700000000054';
        $this->userDob = '1975-10-05';
        $this->actorId = 0;
        $this->userId = '111222333444';
    }

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userAccountId = '123456789';
        $this->userAccountEmail = 'test@example.com';
    }

    /**
     * @Given I am signed in
     */
    public function iAmSignedIn()
    {
        $this->userAccountPassword = 'pa33w0rd';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'       => $this->userAccountId,
                    'Email'    => $this->userAccountEmail,
                    'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT)
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

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->userAccountId, $response['Id']);
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
        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

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

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

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
        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

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
        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
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

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
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
                'SiriusUid' => $this->referenceNo,
                'Active'    => true,
                'Expires'   => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->passcode,
                'ActorLpaId'=> $this->actorId,
            ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        $this->apiPost('/v1/actor-codes/summary', [
            'actor-code' => $this->passcode,
            'uid'        => $this->referenceNo,
            'dob'        => $this->userDob
        ], [
            'user-token' => $this->userId
        ]);

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->referenceNo, $response['lpa']['uId']);

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
        $this->actorAccountCreateData = [
            'Id'                  => 1,
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => []
        ]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Email' => $this->actorAccountCreateData['Email'],
                'ActivationToken' => $this->actorAccountCreateData['ActivationToken']
            ])
        ]));

        $this->apiPost('/v1/user', [
            'email' => $this->actorAccountCreateData['Email'],
            'password' => $this->actorAccountCreateData['Password']
        ]);
        assertEquals($this->actorAccountCreateData['Email'], $this->getResponseAsJson()['Email']);

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
    public function myLPAIsSuccessfullyAdded()
    {
        $now = (new DateTime)->format('Y-m-d\TH:i:s.u\Z');

        // ActorCodes::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid' => $this->referenceNo,
                'Active'    => true,
                'Expires'   => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->passcode,
                'ActorLpaId'=> $this->actorId,
            ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // UserLpaActorMap::create
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'        => $this->userAccountId,
                'UserId'    => $this->userId,
                'SiriusUid' => $this->referenceNo,
                'ActorId'   => $this->actorId,
                'Added'     => $now,
            ])
        ]));

        // ActorCodes::flagCodeAsUsed
        $this->awsFixtures->append(new Result([]));

        $this->apiPost('/v1/actor-codes/confirm', [
            'actor-code' => $this->passcode,
            'uid'        => $this->referenceNo,
            'dob'        => $this->userDob
        ], [
            'user-token' => $this->userId
        ]);

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CREATED);

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

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        $this->apiPost('/v1/actor-codes/summary', [
            'actor-code' => $this->passcode,
            'uid'        => $this->referenceNo,
            'dob'        => $this->userDob
        ], [
            'user-token' => $this->userId
        ]);
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);

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
            'user-token' => $this->userId
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
        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEmpty($response);
    }
      
    /*
     * @When I create an account using duplicate details
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->actorAccountCreateData = [
            'Id'                  => 1,
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'AccountActivationToken'  => $this->actorAccountCreateData['ActivationToken'] ,
                    'Email' => $this->actorAccountCreateData['Email'],
                    'Password' => $this->actorAccountCreateData['Password']
                ])
            ]
        ]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Email' => $this->actorAccountCreateData['Email'],
                'ActivationToken' => $this->actorAccountCreateData['ActivationToken']
            ])
        ]));

        $this->apiPost('/v1/user', [
            'email' => $this->actorAccountCreateData['Email'],
            'password' => $this->actorAccountCreateData['Password']
        ]);
        assertContains('User already exists with email address' . ' ' . $this->actorAccountCreateData['Email'], $this->getResponseAsJson());

    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->actorAccountCreateData = [
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
        assertEquals('activate1234567890', $this->actorAccountCreateData['ActivationToken']);
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
                    'Id'     => $this->actorAccountCreateData['Id']
                ])
            ]
        ]));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => $this->actorAccountCreateData['Id']
            ])
        ]));

        $this->apiPatch('/v1/user-activation', ['activation_token' => $this->actorAccountCreateData['ActivationToken']]);

        $this->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->actorAccountCreateData['Id'], $response['Id']);
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

        $this->apiPatch('/v1/user-activation', ['activation_token' => $this->actorAccountCreateData['ActivationToken']]);

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
     * Convert a key/value array to a correctly marshaled AwsResult structure.
     *
     * AwsResult data is in a special array format that tells you
     * what datatype things are. This function creates that data structure.
     *
     * @param array $input
     * @return array
     */
    protected function marshalAwsResultData(array $input): array
    {
        $marshaler = new Marshaler();

        return $marshaler->marshalItem($input);
    }
}