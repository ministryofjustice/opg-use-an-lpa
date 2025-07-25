<?php

declare(strict_types=1);

namespace BehatTest\Context\Acceptance;

use Aws\Result;
use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\BaseAcceptanceContextTrait;
use BehatTest\Context\SetupEnv;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;

use function PHPUnit\Framework\assertArrayHasKey;

class AccountContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    public array $passwordResetData;
    public array $userAccountCreateData;
    public string $newEmail;
    public string $userEmailResetToken;

    #[Given('/^I access the login form$/')]
    public function iAccessTheLoginForm(): void
    {
        // Not needed in this context
    }

    #[When('/^I enter correct credentials$/')]
    public function iEnterCorrectCredentials(): void
    {
        // Not needed in this context
    }

    #[Given('I am currently signed in')]
    #[Then('/^I am signed in$/')]
    public function iAmCurrentlySignedIn(): void
    {
        $this->base->userAccountPassword = 'pa33w0rd';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->base->userAccountId,
                    'Email'     => $this->base->userAccountEmail,
                    'Password'  => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                    'LastLogin' => null,
                ]),
            ],
        ]));

        // ActorUsers::recordSuccessfulLogin
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->base->userAccountId,
                    'LastLogin' => null,
                ]),
            ],
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
            'password' => $this->base->userAccountPassword,
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertEquals($this->base->userAccountId, $response['Id']);
    }

    #[When('/^I enter incorrect login password$/')]
    public function iEnterIncorrectLoginPassword(): void
    {
        // Not needed in this context
    }

    #[When('/^I enter incorrect login email$/')]
    public function iEnterIncorrectLoginEmail(): void
    {
        // Not needed in this context
    }

    #[Then('/^my account cannot be found$/')]
    public function myAccountCannotBeFound(): void
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/auth', [
            'email'    => 'incorrect@email.com',
            'password' => $this->base->userAccountPassword,
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_NOT_FOUND);
    }

    #[Then('/^I am told my credentials are incorrect$/')]
    public function iAmToldMyCredentialsAreIncorrect(): void
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->base->userAccountId,
                    'Email'     => $this->base->userAccountEmail,
                    'Password'  => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                    'LastLogin' => null,
                ]),
            ],
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
            'password' => '1nc0rr3ctPa33w0rd',
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
    }

    #[Given('/^I have not activated my account$/')]
    public function iHaveNotActivatedMyAccount(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am told my account has not been activated$/')]
    public function iAmToldMyAccountHasNotBeenActivated(): void
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'              => $this->base->userAccountId,
                    'Email'           => $this->base->userAccountEmail,
                    'Password'        => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                    'LastLogin'       => null,
                    'ActivationToken' => 'a12b3c4d5e',
                ]),
            ],
        ]));

        $this->apiPatch('/v1/auth', [
            'email'    => $this->base->userAccountEmail,
            'password' => $this->base->userAccountPassword,
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_UNAUTHORIZED);
    }

    #[Given('I have forgotten my password')]
    public function iHaveForgottenMyPassword(): void
    {
        // Not needed for this context
    }

    #[When('I ask for my password to be reset')]
    public function iAskForMyPasswordToBeReset(): void
    {
        $this->passwordResetData = [
            'Id'                 => $this->base->userAccountId,
            'PasswordResetToken' => 'AAAABBBBCCCC',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail,
                ]),
            ],
        ]));

        // ActorUsers::requestPasswordReset
        $this->awsFixtures->append(new Result([
            'Attributes' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'PasswordResetToken'  => $this->passwordResetData['PasswordResetToken'],
                'PasswordResetExpiry' => time() + (60 * 60 * 24), // 24 hours in the future
            ]),
        ]));

        $this->apiPatch('/v1/request-password-reset', ['email' => $this->base->userAccountEmail], []);
    }

    #[Then('I receive unique instructions on how to reset my password')]
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertEquals($this->base->userAccountId, $response['Id']);
        Assert::assertEquals($this->passwordResetData['PasswordResetToken'], $response['PasswordResetToken']);
    }

    #[Given('I have asked for my password to be reset')]
    public function iHaveAskedForMyPasswordToBeReset(): void
    {
        $this->passwordResetData = [
            'Id'                  => $this->base->userAccountId,
            'PasswordResetToken'  => 'AAAABBBBCCCC',
            'PasswordResetExpiry' => time() + (60 * 60 * 12), // 12 hours in the future
        ];
    }

    #[When('I follow my unique instructions on how to reset my password')]
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword(): void
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
            ]),
        ]));

        $this->apiGet('/v1/can-password-reset?token=' . $this->passwordResetData['PasswordResetToken'], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertEquals($this->base->userAccountId, $response['Id']);
    }

    #[When('I choose a new password')]
    public function iChooseANewPassword(): void
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
            ]),
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/complete-password-reset', [
            'token'    => $this->passwordResetData['PasswordResetToken'],
            'password' => 'newPassw0rd',
        ], []);
    }

    #[Then('my password has been associated with my user account')]
    public function myPasswordHasBeenAssociatedWithMyUserAccount(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertIsArray($response); // empty array response
    }

    #[When('I follow my unique expired instructions on how to reset my password')]
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword(): void
    {
        // expire the password reset token
        $this->passwordResetData['PasswordResetExpiry'] = time() - (60 * 60 * 12); // 12 hours in the past

        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
            ]),
        ]));

        $this->apiGet('/v1/can-password-reset?token=' . $this->passwordResetData['PasswordResetToken'], []);
    }

    #[Then('I am told that my instructions have expired')]
    public function iAmToldThatMyInstructionsHaveExpired(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    /**
     * Typically this endpoint wouldn't be called as we stop at the previous step, in this
     * case though we're using it to test that the endpoint still denies an expired token
     * when directly calling the reset
     */
    #[Then('I am unable to continue to reset my password')]
    public function iAmUnableToContinueToResetMyPassword(): void
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->base->userAccountId,
                    'Email' => $this->base->userAccountEmail,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'                  => $this->base->userAccountId,
                'Email'               => $this->base->userAccountEmail,
                'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
            ]),
        ]));

        $this->apiPatch('/v1/complete-password-reset', [
            'token'    => $this->passwordResetData['PasswordResetToken'],
            'password' => 'newPassw0rd',
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    #[Given('I am not a user of the lpa application')]
    public function iAmNotaUserOftheLpaApplication(): void
    {
        // Not needed for this context
    }

    #[Given('I want to create a new account')]
    public function iWantTocreateANewAccount(): void
    {
        // Not needed for this context
    }

    #[When('I create an account')]
    public function iCreateAnAccount(): void
    {
        $this->userAccountCreateData = [
            'Id'              => 1,
            'ActivationToken' => 'activate1234567890',
            'Email'           => 'test@test.com',
            'Password'        => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [],
        ]));

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        $this->apiPost('/v1/user', [
            'email'    => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password'],
        ], []);

        $result = $this->getResponseAsJson();
        assertArrayHasKey('Id', $result);
        assertArrayHasKey('ActivationToken', $result);
        assertArrayHasKey('ExpiresTTL', $result);
    }

    #[When('I create an account using duplicate details not yet activated')]
    public function iCreateAnAccountUsingDuplicateDetailsNotActivated(): void
    {
        $this->userAccountCreateData = [
            'Id'              => '1234567890abcdef',
            'ActivationToken' => 'activate1234567890',
            'ExpiresTTL'      => '232424232244',
            'Email'           => 'test@test.com',
            'Password'        => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'ActivationToken' => $this->userAccountCreateData['ActivationToken'] ,
                    'Email'           => $this->userAccountCreateData['Email'],
                    'Password'        => $this->userAccountCreateData['Password'],
                    'Id'              => $this->userAccountCreateData['Id'],
                    'ExpiresTTL'      => $this->userAccountCreateData['ExpiresTTL'],
                ]),
            ],
        ]));

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'ActivationToken' => $this->userAccountCreateData['ActivationToken'] ,
                    'ExpiresTTL'      => $this->userAccountCreateData['ExpiresTTL'],
                    'Email'           => $this->userAccountCreateData['Email'],
                    'Password'        => $this->userAccountCreateData['Password'],
                    'Id'              => $this->userAccountCreateData['Id'],
                ]),
            ],
        ]));

        // ActorUsers::resetActivationDetails
        $this->awsFixtures->append(new Result([
            'Item'
                => $this->marshalAwsResultData([
                    'ActivationToken' => $this->userAccountCreateData['ActivationToken'] ,
                    'Email'           => $this->userAccountCreateData['Email'],
                    'Password'        => $this->userAccountCreateData['Password'],
                    'Id'              => $this->userAccountCreateData['Id'],
                ]),
        ]));


        $this->apiPost('/v1/user', [
            'email'    => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password'],
        ], []);
        Assert::assertEquals($this->userAccountCreateData['Email'], $this->getResponseAsJson()['Email']);
    }

    #[When('I create an account using duplicate details')]
    public function iCreateAnAccountUsingDuplicateDetails(): void
    {
        $this->userAccountCreateData = [
            'Id'              => '1234567890abcdef',
            'ActivationToken' => 'activate1234567890',
            'Email'           => 'test@test.com',
            'Password'        => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->userAccountCreateData['Email'],
                ]),
            ],
        ]));

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Email' => $this->userAccountCreateData['Email'],
                ]),
            ],
        ]));

        $this->apiPost('/v1/user', [
            'email'    => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password'],
        ], []);
        Assert::assertContains(
            'User already exists with email address ' . $this->userAccountCreateData['Email'],
            $this->getResponseAsJson()
        );
    }

    #[Given('I have asked to create a new account')]
    public function iHaveAskedToCreateANewAccount(): void
    {
        $this->userAccountCreateData = [
            'Id'                    => '11',
            'ActivationToken'       => 'activate1234567890',
            'ActivationTokenExpiry' => time() + (60 * 60 * 12), // 12 hours in the future
        ];
    }

    #[Then('I am informed about an existing account')]
    #[Then('I send the activation email again')]
    public function iAmInformedAboutAnExistingAccount(): void
    {
        Assert::assertEquals('activate1234567890', $this->userAccountCreateData['ActivationToken']);
    }

    #[Then('I receive unique instructions on how to activate my account')]
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount(): void
    {
        $emailTemplate = 'AccountActivationEmail';

        $this->apiPost(
            '/v1/email-user/' . $emailTemplate,
            [
                'recipient' => 'test@example.com',
                'locale'    => 'cy_GB',
                'activateAccountUrl'
                    => 'http://localhost:9002/cy/activate-account/8tjX_FtUzTrKc9ZtCk8HIQgczYLSX1Ys5paeNjuQFsE=',
            ],
            []
        );
    }

    #[When('I follow the instructions on how to activate my account')]
    public function iFollowTheInstructionsOnHowToActivateMyAccount(): void
    {

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id' => $this->userAccountCreateData['Id'],
                ]),
            ],
        ]));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => $this->userAccountCreateData['Id'],
            ]),
        ]));

        $this->apiPatch(
            '/v1/user-activation',
            [
                'activation_token' => $this->userAccountCreateData['ActivationToken'],
            ],
            []
        );

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();
        Assert::assertEquals($this->userAccountCreateData['Id'], $response['Id']);
    }

    #[When('I follow my instructions on how to activate my account after 24 hours')]
    public function iFollowMyInstructionsOnHowToActivateMyAccountAfter24Hours(): void
    {
        // ActorUsers::activate
        $this->awsFixtures->append(new Result(
            [
                'Items' => [],
            ]
        ));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => '1',
            ]),
        ]));

        $this->apiPatch(
            '/v1/user-activation',
            [
                'activation_token' => $this->userAccountCreateData['ActivationToken'],
            ],
            []
        );

        $response = $this->getResponseAsJson();
        Assert::assertContains('User not found for token', $response);
    }

    #[Then('I am told my unique instructions to activate my account have expired')]
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired(): void
    {
        // Not used in this context
    }

    #[Then('my account is activated')]
    public function myAccountIsActivated(): void
    {
        //Not needed in this context
    }

    #[When('/^I ask to change my password$/')]
    public function iAskToChangeMyPassword(): void
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
        $newPassword = 'Successful-Raid-on-the-Cooki3s!';

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'      => $this->base->userAccountId,
            'password'     => $this->base->userAccountPassword,
            'new-password' => $newPassword,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEmpty($response);
    }

    #[Then('/^I am told my password was changed$/')]
    public function iAmToldMyPasswordWasChanged(): void
    {
        // Not needed for this context
    }

    #[Given('/^I cannot enter my current password$/')]
    public function iCannotEnterMyCurrentPassword(): void
    {
        $failedPassword = 'S0meS0rt0fPassw0rd';
        $newPassword    = 'Successful-Raid-on-the-Cooki3s!';

        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/change-password', [
            'user-id'      => $this->base->userAccountId,
            'password'     => $failedPassword,
            'new-password' => $newPassword,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
    }

    #[Then('/^I am told my current password is incorrect$/')]
    public function iAmToldMyCurrentPasswordIsIncorrect(): void
    {
        // Not needed in this context
    }

    #[Given('/^I am on the settings page$/')]
    public function iAmOnTheSettingsPage(): void
    {
        // Not needed in this context
    }

    #[When('/^I request to delete my account$/')]
    #[When('/^I request to remove an LPA$/')]
    #[Then('/^I cannot see my LPA on the dashboard$/')]
    #[Then('/^I can see a flash message confirming that my LPA has been removed$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    #[Then('/^I confirm that I want to remove the LPA$/')]
    public function iConfirmThatIWantToRemoveTheLPA(): void
    {
        // Not needed in this context
    }

    #[Given('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

        // ActorUsers::delete
        $this->awsFixtures->append(new Result([]));

        $this->apiDelete('/v1/delete-account/' . $this->base->userAccountId);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
    }

    #[Given('/^I am logged out of the service and taken to the index page$/')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage(): void
    {
        // Not needed in this context
    }

    #[Given('/^I am on the change email page$/')]
    public function iAmOnTheChangeEmailPage(): void
    {
        $this->newEmail            = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';
    }

    #[When('/^I request to change my email with an incorrect password$/')]
    public function iRequestToChangeMyEmailWithAnIncorrectPassword(): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'   => $this->base->userAccountId,
            'new-email' => $this->newEmail,
            'password'  => 'inc0rr3cT',
        ], []);
    }

    #[Then('/^I should be told that I could not change my email because my password is incorrect$/')]
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_FORBIDDEN);
    }

    #[When('/^I request to change my email to an email address that (.*)$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressThat($context): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

        if ($context === 'is taken by another user on the service') {
            // ActorUsers::getByEmail (exists)
            $this->awsFixtures->append(
                new Result([
                    'Items' => [
                        $this->marshalAwsResultData([
                            'Email'    => $this->base->userAccountEmail,
                            'Password' => $this->base->userAccountPassword,
                        ]),
                    ],
                ])
            );
        } else {
            $this->awsFixtures->append(new Result([]));
        }

        switch ($context) {
            case 'another user has requested to change their email to but their token has not expired':
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
                            'Password'         => 'otherU53rsPa55w0rd',
                        ]),
                    ],
                ]));
                break;
            case 'another user has requested to change their email to but their token has expired':
                // ActorUsers::getUserByNewEmail
                $this->awsFixtures->append(new Result([
                    'Items' => [
                        $this->marshalAwsResultData([
                            'EmailResetExpiry' => time() - (60),
                            'Email'            => 'another@user.com',
                            'LastLogin'        => null,
                            'Id'               => 'aaaaaa1111111',
                            'NewEmail'         => $this->newEmail,
                            'EmailResetToken'  => 't0ken12345',
                            'Password'         => 'otherU53rsPa55w0rd',
                        ]),
                    ],
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
                        'Password'         => $this->base->userAccountPassword,
                    ]),
                ]));

                break;
        }

        $this->apiPatch('/v1/request-change-email', [
            'user-id'   => $this->base->userAccountId,
            'new-email' => $this->newEmail,
            'password'  => $this->base->userAccountPassword,
        ], []);
    }

    #[Then('/^I should be told my email change request was successful$/')]
    public function iShouldBeToldMyEmailChangeRequestWasSuccessful(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CONFLICT);
    }

    #[When('/^I do not confirm cancellation of the chosen viewer code/')]
    #[When('/^I request to return to the dashboard page/')]
    public function iDoNotConfirmCancellationOfTheChosenViewerCode(): void
    {
        // Not needed for this context
    }

    #[Then('/^I should be sent an email to both my current and new email$/')]
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail(): void
    {
        // Not needed for this context
    }

    #[Given('/^I should be told that my request was successful$/')]
    public function iShouldBeToldThatMyRequestWasSuccessful(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEquals($this->base->userAccountId, $response['Id']);
        Assert::assertEquals($this->base->userAccountEmail, $response['Email']);
        Assert::assertEquals($this->newEmail, $response['NewEmail']);
        Assert::assertEquals($this->base->userAccountPassword, $response['Password']);
        Assert::assertEquals($this->userEmailResetToken, $response['EmailResetToken']);
        Assert::assertArrayHasKey('EmailResetExpiry', $response);
    }

    #[When('/^I request to change my email to a unique email address$/')]
    public function iRequestToChangeMyEmailToAUniqueEmailAddress(): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
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
                'Password'         => $this->base->userAccountPassword,
            ]),
        ]));

        $this->apiPatch('/v1/request-change-email', [
            'user-id'   => $this->base->userAccountId,
            'new-email' => $this->newEmail,
            'password'  => $this->base->userAccountPassword,
        ]);
    }

    #[Given('/^I have requested to change my email address$/')]
    public function iHaveRequestedToChangeMyEmailAddress(): void
    {
        $this->userEmailResetToken = '12345abcde';
        $this->newEmail            = 'newEmail@test.com';
    }

    #[Given('/^My email reset token is still valid$/')]
    public function myEmailResetTokenIsStillValid(): void
    {
        // Not needed for this context
    }

    #[When('/^I click the link to verify my new email address$/')]
    public function iClickTheLinkToVerifyMyNewEmailAddress(): void
    {
        // canResetEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken' => $this->userEmailResetToken,
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->base->userAccountId,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->base->userAccountId,
                'Email'            => $this->base->userAccountEmail,
                'Password'         => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                'EmailResetExpiry' => time() + (60 * 60),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
            ]),
        ]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEquals($this->base->userAccountId, $response['Id']);

        //completeChangeEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken' => $this->userEmailResetToken,
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->base->userAccountId,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->base->userAccountId,
                'Email'            => $this->base->userAccountEmail,
                'Password'         => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                'EmailResetExpiry' => time() + (60 * 60),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
            ]),
        ]));

        // ActorUsers::changeEmail
        $this->awsFixtures->append(new Result([]));

        $this->apiPatch('/v1/complete-change-email', [
            'reset_token' => $this->userEmailResetToken,
        ]);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);

        $response = $this->getResponseAsJson();

        Assert::assertEquals([], $response);
    }

    #[Then('/^My account email address should be reset$/')]
    public function myAccountEmailAddressShouldBeReset(): void
    {
        // Not needed for this context
    }

    #[Given('/^I should be able to login with my new email address$/')]
    public function iShouldBeAbleToLoginWithMyNewEmailAddress(): void
    {
        // Not needed for this context
    }

    #[When('/^I click the link to verify my new email address after my token has expired$/')]
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired(): void
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'EmailResetToken' => $this->userEmailResetToken,
                ]),
                $this->marshalAwsResultData([
                    'Id' => $this->base->userAccountId,
                ]),
            ],
        ]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'               => $this->base->userAccountId,
                'Email'            => $this->base->userAccountEmail,
                'Password'         => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
                'EmailResetExpiry' => time() - (60 * 60),
                'LastLogin'        => null,
                'NewEmail'         => $this->newEmail,
                'EmailResetToken'  => $this->userEmailResetToken,
            ]),
        ]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    #[Then('/^I should be told that my email could not be changed$/')]
    public function iShouldBeToldThatMyEmailCouldNotBeChanged(): void
    {
        // Not needed for this context
    }

    #[When('/^I click an old link to verify my new email address containing a token that no longer exists$/')]
    public function iClickAnOldLinkToVerifyMyNewEmailAddressContainingATokenThatNoLongerExists(): void
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([]));

        $this->apiGet('/v1/can-reset-email?token=' . $this->userEmailResetToken, []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_GONE);
    }

    #[When('/^I create an account using with an email address that has been requested for reset$/')]
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset(): void
    {
        $this->base->userAccountId = '123456789';

        $this->userAccountCreateData = [
            'Id'              => 1,
            'ActivationToken' => 'activate1234567890',
            'Email'           => 'test@test.com',
            'Password'        => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [],
        ]));

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'               => $this->base->userAccountId,
                    'Email'            => 'other@user.co.uk',
                    'Password'         => password_hash('passW0rd', PASSWORD_DEFAULT, ['cost' => 13]),
                    'EmailResetExpiry' => time() + (60 * 60),
                    'LastLogin'        => null,
                    'NewEmail'         => 'test@test.com',
                    'EmailResetToken'  => 'abc1234567890',
                ]),
            ],
        ]));

        $this->apiPost('/v1/user', [
            'email'    => $this->userAccountCreateData['Email'],
            'password' => $this->userAccountCreateData['Password'],
        ], []);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_CONFLICT);
    }

    #[Then('/^I am informed that there was a problem with that email address$/')]
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress(): void
    {
        // Not needed for this context
    }

    #[When('/^I request to change my email to an email address without my id$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyId(): void
    {
        $this->apiPatch(
            '/v1/request-change-email',
            [
                'user-id'   => '',
                'new-email' => $this->newEmail,
                'password'  => $this->base->userAccountPassword,
            ]
        );
    }

    #[When('/^I request to change my email to an email address without my new email$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyNewEmail(): void
    {
        $this->apiPatch(
            '/v1/request-change-email',
            [
                'user-id'   => $this->base->userAccountId,
                'new-email' => '',
                'password'  => $this->base->userAccountPassword,
            ]
        );
    }

    #[When('/^I request to change my email to an email address without my password$/')]
    public function iRequestToChangeMyEmailToAnEmailAddressWithoutMyPassword(): void
    {
        $this->apiPatch('/v1/request-change-email', [
            'user-id'   => $this->base->userAccountId,
            'new-email' => $this->newEmail,
            'password'  => '',
        ]);
    }

    /**
     * @throws ExpectationException
     */
    #[Then('/^I should be told that a bad request was made$/')]
    public function iShouldBeToldThatABadRequestWasMade(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    #[When('I view a page and the system message is set')]
    public function iViewAPageAndTheSystemMessageIsSet(): void
    {
        $this->awsFixtures->append(new Result([
          'Parameters' => [
              ['Name' => '/system-message/use/en', 'Value' => 'Use message'],
              ['Name' => '/system-message/use/cy', 'Value' => 'Neges defnyddio'],
          ],
        ]));

        $this->apiGet('/v1/system-message');
    }

    /**
     * @throws ExpectationFailedException|ExpectationException
     * @throws Exception
     */
    #[Then('I see the system message')]
    public function iSeeTheSystemMessage(): void
    {
        $response = $this->getResponseAsJson();

        Assert::assertEquals('Use message', $response['use/en']);
        Assert::assertEquals('Neges defnyddio', $response['use/cy']);
        Assert::assertArrayNotHasKey('view/en', $response);
        Assert::assertArrayNotHasKey('view/cy', $response);

        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
    }
}
