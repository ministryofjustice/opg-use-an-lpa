<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Fig\Http\Message\StatusCodeInterface;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Acceptance
 *
 * @property array passwordResetData
 * @property array userAccountCreateData
 * @property string newEmail
 * @property string userEmailResetToken
 */
class AccountContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

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
        $this->base->userAccountPassword = 'pa33w0rd';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->base->userAccountId,
                    'Email'     => $this->base->userAccountEmail,
                    'Password'  => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin' => null
                ])
            ]
        ]));

        // ActorUsers::recordSuccessfulLogin
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->base->userAccountId,
                    'LastLogin' => null
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
            'password' => $this->base->userAccountPassword
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->base->userAccountId, $response['Id']);
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
            'password' => $this->base->userAccountPassword
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
                    'Id'        => $this->base->userAccountId,
                    'Email'     => $this->base->userAccountEmail,
                    'Password'  => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin' => null
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
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
                    'Id'              => $this->base->userAccountId,
                    'Email'           => $this->base->userAccountEmail,
                    'Password'        => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
                    'LastLogin'       => null,
                    'ActivationToken' => 'a12b3c4d5e'
                ])
            ]
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
            'password' => $this->base->userAccountPassword
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
            'Id'                  => $this->base->userAccountId,
            'PasswordResetToken'  => 'AAAABBBBCCCC'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::requestPasswordReset
        $this->awsFixtures->append(new Result([
            'Attributes' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'PasswordResetToken'  => $this->passwordResetData['PasswordResetToken'],
                'PasswordResetExpiry' => time() + (60 * 60 * 24) // 24 hours in the future
            ])
        ]));

        $this->apiPatch('/v1/request-password-reset', ['email' => $this->base->userAccountEmail], []);
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->base->userAccountId, $response['Id']);
        assertEquals($this->passwordResetData['PasswordResetToken'], $response['PasswordResetToken']);
    }

    /**
     * @Given I have asked for my password to be reset
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->passwordResetData = [
            'Id'                  => $this->base->userAccountId,
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
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry']
            ])
        ]));

        $this->apiGet('/v1/can-password-reset?token=' . $this->passwordResetData['PasswordResetToken'], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        assertEquals($this->base->userAccountId, $response['Id']);
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
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
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
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
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
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
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

        // ActorUsers::getUserByNewEmail
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
     * @When I create an account using duplicate details not yet activated
     */
    public function iCreateAnAccountUsingDuplicateDetailsNotActivated()
    {
        $this->userAccountCreateData = [
            'Id'                  => '1234567890abcdef',
            'ActivationToken'     => 'activate1234567890',
            'ExpiresTTL'          => '232424232244',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'ActivationToken'  => $this->userAccountCreateData['ActivationToken'] ,
                    'Email' => $this->userAccountCreateData['Email'],
                    'Password' => $this->userAccountCreateData['Password'],
                    'Id' => $this->userAccountCreateData['Id'],
                    'ExpiresTTL' => $this->userAccountCreateData['ExpiresTTL'],
                ])
            ]
        ]));

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'ActivationToken'  => $this->userAccountCreateData['ActivationToken'] ,
                    'ExpiresTTL' => $this->userAccountCreateData['ExpiresTTL'],
                    'Email' => $this->userAccountCreateData['Email'],
                    'Password' => $this->userAccountCreateData['Password'],
                    'Id' => $this->userAccountCreateData['Id'],
                ])
            ]
        ]));

        // ActorUsers::resetActivationDetails
        $this->awsFixtures->append(new Result([
            'Item' =>
                $this->marshalAwsResultData([
                    'ActivationToken'  => $this->userAccountCreateData['ActivationToken'] ,
                    'Email' => $this->userAccountCreateData['Email'],
                    'Password' => $this->userAccountCreateData['Password'],
                    'Id' => $this->userAccountCreateData['Id'],
                ])
        ]));


        $this->apiPost('/v1/user', [
            'email' => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password']
        ], []);
        assertEquals($this->userAccountCreateData['Email'], $this->getResponseAsJson()['Email']);
    }


    /**
     * @When I create an account using duplicate details
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $this->userAccountCreateData = [
            'Id'                  => '1234567890abcdef',
            'ActivationToken'     => 'activate1234567890',
            'Email'               => 'test@test.com',
            'Password'            => 'Pa33w0rd'
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->userAccountCreateData['Email'],
                ])
            ]
        ]));

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->userAccountCreateData['Email'],
                ])
            ]
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
     * @Then I send the activation email again
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

        $this->apiPatch(
            '/v1/user-activation',
            [
                'activation_token' => $this->userAccountCreateData['ActivationToken']
            ],
            []
        );

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
            ]
        ));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => '1'
            ])
        ]));

        $this->apiPatch(
            '/v1/user-activation',
            [
                'activation_token' => $this->userAccountCreateData['ActivationToken']
            ],
            []
        );

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
                'Id'       => $this->base->userAccountId,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'       => $this->base->userAccountId,
            'password'      => $this->base->userAccountPassword,
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
                'Id'       => $this->base->userAccountId,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'       => $this->base->userAccountId,
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
     * @When /^I request to remove the added LPA$/
     * @Then /^The removed LPA will not be displayed on the dashboard$/
     * @Then /^I can see a flash message for the removed LPA$/
     */
    public function iRequestToDeleteMyAccount()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     * @Then /^I am asked to confirm whether I am sure if I want to delete lpa$/
     * @Given /^I am on the confirm lpa deletion page$/
     * @When /^I confirm removal of the LPA$/
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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::delete
        $this->awsFixtures->append(new Result([]));

        $this->apiDelete('/v1/delete-account/' . $this->base->userAccountId);

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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->base->userAccountId,
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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->base->userAccountEmail,
                    'Password' => $this->base->userAccountPassword
                ])
            ]
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->base->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->base->userAccountPassword
        ], []);
    }

    /**
     * @Then /^I should be told my request was successful and an email is sent to the chosen email address to warn the user$/
     */
    public function iShouldBeToldMyRequestWasSuccessfulAndAnEmailIsSentToTheChosenEmailAddressToWarnTheUser()
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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetExpiry' => time() + (60 * 60),
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
            'user-id'       => $this->base->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->base->userAccountPassword
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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // Expired
        $otherUsersTokenExpiry = time() - (60);

        // ActorUsers::getUserByNewEmail
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
                'EmailResetExpiry' => time() + (60 * 60 * 48),
                'Email'            => $this->base->userAccountEmail,
                'LastLogin'        => null,
                'Id'               => $this->base->userAccountId,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
                'Password'         => $this->base->userAccountPassword
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->base->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->base->userAccountPassword
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
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($this->base->userAccountId, $response['Id']);
        assertEquals($this->base->userAccountEmail, $response['Email']);
        assertEquals($this->newEmail, $response['NewEmail']);
        assertEquals($this->base->userAccountPassword, $response['Password']);
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
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::recordChangeEmailRequest
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'EmailResetExpiry' => time() + (60 * 60 * 48),
                'Email'            => $this->base->userAccountEmail,
                'LastLogin'        => null,
                'Id'               => $this->base->userAccountId,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
                'Password'         => $this->base->userAccountPassword
            ])
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'       => $this->base->userAccountId,
            'new-email'     => $this->newEmail,
            'password'      => $this->base->userAccountPassword
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
                    'Id' => $this->base->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->base->userAccountId,
                'Email'            => $this->base->userAccountEmail,
                'Password'         => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
                'EmailResetExpiry' => (time() + (60 * 60)),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken
            ])
        ]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        assertEquals($this->base->userAccountId, $response['Id']);

        //completeChangeEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken'  => $this->userEmailResetToken
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->base->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
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
                    'Id' => $this->base->userAccountId
                ])
            ]
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->base->userAccountId,
                'Email'            => $this->base->userAccountEmail,
                'Password'         => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT),
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

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'               => $this->base->userAccountId,
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

    /**
     * @When /^I request to change my email to an email address without my id$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyId()
    {
        $this->apiPatch(
            '/v1/request-change-email',
            [
                'user-id'       => '',
                'new-email'     => $this->newEmail,
                'password'      => $this->base->userAccountPassword
            ]
        );
    }

    /**
     * @When /^I request to change my email to an email address without my new email$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyNewEmail()
    {
        $this->apiPatch(
            '/v1/request-change-email',
            [
                'user-id' => $this->base->userAccountId,
                'new-email' => '',
                'password' => $this->base->userAccountPassword
            ]
        );
    }

    /**
     * @When /^I request to change my email to an email address without my password$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyPassword()
    {
        $this->apiPatch('/v1/request-change-email', [
            'user-id' => $this->base->userAccountId,
            'new-email' => $this->newEmail,
            'password' => ''
        ]);
    }

    /**
     * @Then /^I should be told that a bad request was made$/
     */
    public function iShouldBeToldThatABadRequestWasMade()
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Then /^The LPA is removed$/
     */
    public function theLpaIsRemoved()
    {
        $actorToken = 'token123';
        $siriusUid = '700000001';
        $added = '2020-08-20';
        $actorId = '59';
        $userId = 'user123';

        // userLpaActorMapRepository::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'            => $actorToken,
                'SiriusUid'     => $siriusUid,
                'Added'         => $added,
                'ActorId'       => $actorId,
                'UserId'        => $userId
            ])
        ]));

        //viewerCodesRepository::getCodesByLpaId
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id'            => '1',
                                'ViewerCode'    => '123ABCD6789',
                                'SiriusUid'     => '700000055554',
                                'Added'         => '2021-01-01 00:00:00',
                                'Expires'       => '2021-02-01 00:00:00',
                                'UserLpaActor' => $this->userLpaActorToken,
                                'Organisation' => $this->organisation,
                            ]
                        ),
                    ],
                ]
            )
        );
        //viewerCodesRepository::removeActorAssociation
        // viewerCodesRepository::removeActorAssociation
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'SiriusUid' => $this->lpaUid,
                                'Added' => '2021-01-05 12:34:56',
                                'Expires' => '2022-01-05 12:34:56',
                                'UserLpaActor' => '',
                                'Organisation' => $this->organisation,
                                'ViewerCode' => '123ABCD6789',
                                'Viewed' => false,
                            ]
                        ),
                    ],
                ]
            )
        );

        // userLpaActorMapRepository::delete
        $this->awsFixtures->append(new Result([]));

        $this->apiDelete('/v1/lpas/' . $actorToken);
    }
}
