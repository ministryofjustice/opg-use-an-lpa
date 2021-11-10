<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use App\Service\Log\RequestTracing;
use App\Service\User\UserService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use Exception;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property $userAccountId
 * @property $userAccountEmail
 * @property $passwordResetData
 * @property $userId
 * @property $password
 * @property $userAccountPassword
 * @property $userActivationToken
 * @property $actorAccountCreateData
 * @property $newEmail
 * @property $userEmailResetToken
 */
class AccountContext extends BaseIntegrationContext
{
    use SetupEnv;

    private AwsMockHandler $awsFixtures;

    /**
     * @Given /^I access the login form$/
     */
    public function iAccessTheLoginForm()
    {
        // Not needed in this context
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
     * @Given I am currently signed in
     * @Then /^I am signed in$/
     */
    public function iAmCurrentlySignedIn()
    {
        $this->password = 'pa33w0rd';
        $this->userAccountPassword = 'n3wPassWord';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                                'Password' => password_hash($this->password, PASSWORD_DEFAULT),
                                'LastLogin' => null,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::recordSuccessfulLogin
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'LastLogin' => null,
                            ]
                        ),
                    ],
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        $user = $us->authenticate($this->userAccountEmail, $this->password);

        assertEquals($this->userAccountId, $user['Id']);
        assertEquals($this->userAccountEmail, $user['Email']);
    }

    /**
     * @Then I am informed about an existing account
     * @Then I send the activation email again
     */
    public function iAmInformedAboutAnExistingAccount()
    {
        assertEquals('activate1234567890', $this->actorAccountCreateData['ActivationToken']);
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am logged out of the service and taken to the index page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage()
    {
        // Not needed in this context
    }

    /**
     * @Given I am not a user of the lpa application
     */
    public function iAmNotaUserOftheLpaApplication()
    {
        // Not needed for this context
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
     * @Given /^I am on the dashboard page$/
     * @Given /^I am on the user dashboard page$/
     * @Then /^I cannot see the added LPA$/
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
        // Not needed in this context
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     * @Then /^I cannot see my access codes and their details$/
     */
    public function iAmTakenBackToTheDashboardPage()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told my account has not been activated$/
     */
    public function iAmToldMyAccountHasNotBeenActivated()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                                'LastLogin' => null,
                                'ActivationToken' => 'a12b3c4d5e',
                            ]
                        ),
                    ],
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate($this->userAccountEmail, $this->userAccountPassword);
        } catch (UnauthorizedException $ex) {
            assertEquals(
                'Authentication attempted against inactive account with Id ' . $this->userAccountId,
                $ex->getMessage()
            );
            assertEquals(401, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected unauthorized exception was not thrown');
    }

    /**
     * @Then /^I am told my credentials are incorrect$/
     */
    public function iAmToldMyCredentialsAreIncorrect()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                                'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                                'LastLogin' => null,
                            ]
                        ),
                    ],
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate($this->userAccountEmail, '1nc0rr3ctPa33w0rd');
        } catch (ForbiddenException $fe) {
            assertEquals('Authentication failed for email ' . $this->userAccountEmail, $fe->getMessage());
            assertEquals(403, $fe->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected forbidden exception was not thrown');
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
        // Not needed for this context
    }

    /**
     * @Then I am told my unique instructions to activate my account have expired
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        // Not used in this context
    }

    /**
     * @Then I am told that my instructions have expired
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        // Not used in this context
    }

    /**
     * @Then I am unable to continue to reset my password
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not used in this context
    }

    /**
     * @When I ask for my password to be reset
     */
    public function iAskForMyPasswordToBeReset()
    {
        $resetToken = 'AAAABBBBCCCC';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::requestPasswordReset
        $this->awsFixtures->append(
            new Result(
                [
                    'Attributes' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'PasswordResetToken' => $resetToken,
                            'PasswordResetExpiry' => time() + (60 * 60 * 24) // 24 hours in the future
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        $this->passwordResetData = $us->requestPasswordReset($this->userAccountEmail);
    }

    /**
     * @When /^I ask to change my password$/
     */
    public function iAskToChangeMyPassword()
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

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Password' => password_hash($failedPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        $us->completeChangePassword(
            $this->userAccountId,
            new HiddenString($failedPassword),
            new HiddenString($newPassword)
        );

        $command = $this->awsFixtures->getLastCommand();

        assertEquals('actor-users', $command['TableName']);
        assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        assertEquals('UpdateItem', $command->getName());
    }

    /**
     * @When I choose a new password
     */
    public function iChooseANewPassword()
    {
        $password = 'newPass0rd';

        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        $us->completePasswordReset($this->passwordResetData['PasswordResetToken'], new HiddenString($password));
    }

    /**
     * @When /^I click an old link to verify my new email address containing a token that no longer exists$/
     */
    public function iClickAnOldLinkToVerifyMyNewEmailAddressContainingATokenThatNoLongerExists()
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([]));

        $userService = $this->container->get(UserService::class);

        try {
            $userService->canResetEmail($this->userEmailResetToken);
        } catch (GoneException $ex) {
            assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress()
    {
        // canResetEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'EmailResetToken' => $this->userEmailResetToken,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                            'EmailResetExpiry' => time() + (60 * 60),
                            'LastLogin' => null,
                            'NewEmail' => $this->newEmail,
                            'EmailResetToken' => $this->userEmailResetToken,
                        ]
                    ),
                ]
            )
        );

        $userService = $this->container->get(UserService::class);

        $userId = $userService->canResetEmail($this->userEmailResetToken);

        assertEquals($this->userAccountId, $userId);

        //completeChangeEmail

        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'EmailResetToken' => $this->userEmailResetToken,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                            'EmailResetExpiry' => (time() + (60 * 60)),
                            'LastLogin' => null,
                            'NewEmail' => $this->newEmail,
                            'EmailResetToken' => $this->userEmailResetToken,
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::changeEmail
        $this->awsFixtures->append(new Result([]));

        $reset = $userService->completeChangeEmail($this->userEmailResetToken);

        assertNull($reset);
    }

    /**
     * @When /^I click the link to verify my new email address after my token has expired$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired()
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'EmailResetToken' => $this->userEmailResetToken,
                            ]
                        ),
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                            'EmailResetExpiry' => (time() - (60 * 60)),
                            'LastLogin' => null,
                            'NewEmail' => $this->newEmail,
                            'EmailResetToken' => $this->userEmailResetToken,
                        ]
                    ),
                ]
            )
        );

        $userService = $this->container->get(UserService::class);

        try {
            $userService->canResetEmail($this->userEmailResetToken);
        } catch (GoneException $ex) {
            assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount()
    {
        // Not needed in this context
    }

    /**
     * @When I create an account
     */
    public function iCreateAnAccount()
    {
        $this->userAccountEmail = 'hello@test.com';
        $this->userAccountPassword = 'n3wPassWord';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ]
            )
        );

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Email' => $this->userAccountEmail,
                            'ActivationToken' => '123456789',
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        $this->userActivationToken = $us->add(
            [
                'email' => $this->userAccountEmail,
                'password' => new HiddenString($this->userAccountPassword),
            ]
        )['ActivationToken'];
    }

    /**
     * @When I create an account using duplicate details
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $userAccountCreateData = [
            'email' => 'hello@test.com',
            'password' => 'n3wPassWord',
        ];

        $id = '1234567890abcdef';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Email' => $userAccountCreateData['email'],
                                'Password' => $userAccountCreateData['password'],
                            ]
                        ),
                    ],
                ]
            )
        );
        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Email' => $userAccountCreateData['email'],
                                'Password' => $userAccountCreateData['password'],
                                'Id' => $id,
                            ]
                        ),
                    ],
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        try {
            $us->add(['email' => $userAccountCreateData['email'], 'password' => $userAccountCreateData['password']]);
        } catch (ConflictException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When I create an account using duplicate details not yet activated
     */
    public function iCreateAnAccountUsingDuplicateDetailsNotActivated()
    {
        $userAccountCreateData = [
            'Id' => '1234567890abcdef',
            'ActivationToken' => 'activate1234567890',
            'ExpiresTTL' => '232424232244',
            'Email' => 'test@test.com',
            'Password' => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'ActivationToken' => $userAccountCreateData['ActivationToken'],
                                'Email' => $userAccountCreateData['Email'],
                                'Password' => $userAccountCreateData['Password'],
                                'Id' => $userAccountCreateData['Id'],
                                'ExpiresTTL' => $userAccountCreateData['ExpiresTTL'],
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'ActivationToken' => $userAccountCreateData['ActivationToken'],
                                'ExpiresTTL' => $userAccountCreateData['ExpiresTTL'],
                                'Email' => $userAccountCreateData['Email'],
                                'Password' => $userAccountCreateData['Password'],
                                'Id' => $userAccountCreateData['Id'],
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::resetActivationDetails
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' =>
                        $this->marshalAwsResultData(
                            [
                                'ActivationToken' => $userAccountCreateData['ActivationToken'],
                                'Email' => $userAccountCreateData['Email'],
                                'Password' => $userAccountCreateData['Password'],
                                'Id' => $userAccountCreateData['Id'],
                            ]
                        ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);


        $result = $us->add(
            [
                'email' => $userAccountCreateData['Email'],
                'password' => new HiddenString($userAccountCreateData['Password']),
            ]
        );
        assertEquals($result['Email'], $userAccountCreateData['Email']);
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $userAccountCreateData = [
            'Id' => 1,
            'ActivationToken' => 'activate1234567890',
            'Email' => 'test@test.com',
            'Password' => 'Pa33w0rd',
        ];

        // ActorUsers::getByEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ]
            )
        );

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => 'other@user.co.uk',
                                'Password' => password_hash('passW0rd', PASSWORD_DEFAULT),
                                'EmailResetExpiry' => (time() + (60 * 60)),
                                'LastLogin' => null,
                                'NewEmail' => 'test@test.com',
                                'EmailResetToken' => 'abc1234567890',
                            ]
                        ),
                    ],
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        try {
            $us->add(['email' => $userAccountCreateData['Email'], 'password' => $userAccountCreateData['Password']]);
        } catch (ConflictException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When /^I enter correct credentials$/
     */
    public function iEnterCorrectCredentials()
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
     * @When /^I enter incorrect login password$/
     */
    public function iEnterIncorrectLoginPassword()
    {
        // Not needed in this context
    }

    /**
     * @When I follow my instructions on how to activate my account after 24 hours
     */
    public function iFollowMyInstructionsOnHowToActivateMyAccountAfter24Hours()
    {
        // ActorUsers::activate
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [],
                ]
            )
        );

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => '1',
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);
        try {
            $us->activate($this->actorAccountCreateData['ActivationToken']);
        } catch (Exception $ex) {
            assertEquals('User not found for token', $ex->getMessage());
        }
    }

    /**
     * @When I follow my unique expired instructions on how to reset my password
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        // ActorUsers::getIdByPasswordResetToken
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        try {
            $us->canResetPassword($this->passwordResetData['PasswordResetToken']);
        } catch (GoneException $gex) {
            assertEquals('Reset token not found', $gex->getMessage());
        }
    }

    /**
     * @When I follow my unique instructions on how to reset my password
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        // ActorUsers::activate
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => $this->userAccountId,
                                'Email' => $this->userAccountEmail,

                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'PasswordResetExpiry' => $this->passwordResetData['PasswordResetExpiry'],
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        $userId = $us->canResetPassword($this->passwordResetData['PasswordResetToken']);

        assertEquals($this->userAccountId, $userId);
    }

    /**
     * @When I follow the instructions on how to activate my account
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount()
    {
        // ActorUsers::activate
        $this->awsFixtures->append(
            new Result(
                [
                    'Items' => [
                        $this->marshalAwsResultData(
                            [
                                'Id' => '1',
                            ]
                        ),
                    ],
                ]
            )
        );

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => '123456789',
                        ]
                    ),
                ]
            )
        );

        $us = $this->container->get(UserService::class);

        $userData = $us->activate($this->actorAccountCreateData['ActivationToken']);

        assertNotNull($userData);
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     * @When /^I request to return to the dashboard page/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        // Not needed for this context
    }

    /**
     * @Given I have asked for my password to be reset
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->passwordResetData = [
            'Id' => $this->userAccountId,
            'PasswordResetToken' => 'AAAABBBBCCCC',
            'PasswordResetExpiry' => time() + (60 * 60 * 12) // 12 hours in the future
        ];
    }

    /**
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->actorAccountCreateData = [
            'Id' => '123456789',
            'Email' => 'hello@test.com',
            'Password' => 'Pa33w0rd',
            'ActivationToken' => 'activate1234567890',
            'ActivationTokenExpiry' => time() + (60 * 60 * 12) // 12 hours in the future
        ];
    }

    /**
     * @Given I have forgotten my password
     */
    public function iHaveForgottenMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have not activated my account$/
     */
    public function iHaveNotActivatedMyAccount()
    {
        // Not needed for this context
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
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        $us->completeChangePassword(
            $this->userAccountId,
            new HiddenString($this->userAccountPassword),
            new HiddenString($newPassword)
        );

        $command = $this->awsFixtures->getLastCommand();

        assertEquals('actor-users', $command['TableName']);
        assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        assertEquals('UpdateItem', $command->getName());
    }

    /**
     * @Then I receive unique instructions on how to activate my account
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        assertEquals('123456789', $this->userActivationToken);
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        assertArrayHasKey('PasswordResetToken', $this->passwordResetData);
    }

    /**
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress()
    {
        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::getByEmail (exists)
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::getUserByNewEmail
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::recordChangeEmailRequest
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'EmailResetExpiry' => time() + (60 * 60 * 48),
                            'Email' => $this->userAccountEmail,
                            'LastLogin' => null,
                            'Id' => $this->userAccountId,
                            'NewEmail' => $this->newEmail,
                            'EmailResetToken' => $this->userEmailResetToken,
                            'Password' => $this->userAccountPassword,
                        ]
                    ),
                ]
            )
        );
    }

//    /**
//     * @When /^I request to change my email to an email address that another user has requested to change their email to but their token has expired$/
//     */
//    public function iRequestToChangeMyEmailToAnEmailAddressThatAnotherUserHasRequestedToChangeTheirEmailToButTheirTokenHasExpired()
//    {
//        // ActorUsers::get
//        $this->awsFixtures->append(
//            new Result(
//                [
//                    'Item' => $this->marshalAwsResultData(
//                        [
//                            'Id' => $this->userAccountId,
//                            'Email' => $this->userAccountEmail,
//                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
//                        ]
//                    ),
//                ]
//            )
//        );
//
//        // ActorUsers::getByEmail (exists)
//        $this->awsFixtures->append(new Result([]));
//
//        // Expired
//        $otherUsersTokenExpiry = time() - (60);
//
//        // ActorUsers::getUserByNewEmail
//        $this->awsFixtures->append(
//            new Result(
//                [
//                    'Items' => [
//                        $this->marshalAwsResultData(
//                            [
//                                'EmailResetExpiry' => $otherUsersTokenExpiry,
//                                'Email' => 'another@user.com',
//                                'LastLogin' => null,
//                                'Id' => 'aaaaaa1111111',
//                                'NewEmail' => $this->newEmail,
//                                'EmailResetToken' => 't0ken12345',
//                                'Password' => 'otherU53rsPa55w0rd',
//                            ]
//                        ),
//                    ],
//                ]
//            )
//        );
//
//        // ActorUsers::recordChangeEmailRequest
//        $this->awsFixtures->append(
//            new Result(
//                [
//                    'Item' => $this->marshalAwsResultData(
//                        [
//                            'EmailResetExpiry' => time() + (60 * 60 * 48),
//                            'Email' => $this->userAccountEmail,
//                            'LastLogin' => null,
//                            'Id' => $this->userAccountId,
//                            'NewEmail' => $this->newEmail,
//                            'EmailResetToken' => $this->userEmailResetToken,
//                            'Password' => $this->userAccountPassword,
//                        ]
//                    ),
//                ]
//            )
//        );
//    }
//
//    /**
//     * @When /^I request to change my email to an email address that another user has requested to change their email to but their token has not expired$/
//     */
//    public function iRequestToChangeMyEmailToAnEmailAddressThatAnotherUserHasRequestedToChangeTheirEmailToButTheirTokenHasNotExpired()
//    {
//        // ActorUsers::get
//        $this->awsFixtures->append(
//            new Result(
//                [
//                    'Item' => $this->marshalAwsResultData(
//                        [
//                            'Id' => $this->userAccountId,
//                            'Email' => $this->userAccountEmail,
//                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
//                        ]
//                    ),
//                ]
//            )
//        );
//
//        // ActorUsers::getByEmail (exists)
//        $this->awsFixtures->append(new Result([]));
//
//        // ActorUsers::getUserByNewEmail
//        $this->awsFixtures->append(
//            new Result(
//                [
//                    'Items' => [
//                        $this->marshalAwsResultData(
//                            [
//                                'EmailResetExpiry' => time() + (60 * 60),
//                                'Email' => 'another@user.com',
//                                'LastLogin' => null,
//                                'Id' => 'aaaaaa1111111',
//                                'NewEmail' => $this->newEmail,
//                                'EmailResetToken' => 't0ken12345',
//                                'Password' => 'otherU53rsPa55w0rd',
//                            ]
//                        ),
//                    ],
//                ]
//            )
//        );
//
//        $userService = $this->container->get(UserService::class);
//
//        try {
//            $userService->requestChangeEmail(
//                $this->userAccountId,
//                $this->newEmail,
//                new HiddenString($this->userAccountPassword)
//            );
//        } catch (ConflictException $ex) {
//            assertEquals(409, $ex->getCode());
//            return;
//        }
//
//        throw new ExpectationFailedException('Conflict exception was not thrown');
//    }

    /**
     * @When /^I request to change my email to an email address that (.*)$/
     */
    public function iRequestToChangeMyEmailToAnEmailAddressThat($context)
    {
        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        if ($context === 'is taken by another user on the service') {
            // ActorUsers::getByEmail (exists)
            $this->awsFixtures->append(
                new Result(
                    [
                        'Items' => [
                            $this->marshalAwsResultData(
                                [
                                    'Email' => $this->userAccountEmail,
                                    'Password' => $this->userAccountPassword,
                                ]
                            ),
                        ],
                    ]
                )
            );
        } else {
            $this->awsFixtures->append(new Result([]));
        }

        switch ($context) {
            case 'another user has requested to change their email to but their token has not expired':
                // ActorUsers::getUserByNewEmail
                $this->awsFixtures->append(
                    new Result(
                        [
                            'Items' => [
                                $this->marshalAwsResultData(
                                    [
                                        'EmailResetExpiry' => time() + (60 * 60),
                                        'Email' => 'another@user.com',
                                        'LastLogin' => null,
                                        'Id' => 'aaaaaa1111111',
                                        'NewEmail' => $this->newEmail,
                                        'EmailResetToken' => 't0ken12345',
                                        'Password' => 'otherU53rsPa55w0rd',
                                    ]
                                ),
                            ],
                        ]
                    )
                );
                break;
            case 'another user has requested to change their email to but their token has expired':
                // ActorUsers::getUserByNewEmail
                $this->awsFixtures->append(
                    new Result(
                        [
                            'Items' => [
                                $this->marshalAwsResultData(
                                    [
                                        'EmailResetExpiry' => time() - (60),
                                        'Email' => 'another@user.com',
                                        'LastLogin' => null,
                                        'Id' => 'aaaaaa1111111',
                                        'NewEmail' => $this->newEmail,
                                        'EmailResetToken' => 't0ken12345',
                                        'Password' => 'otherU53rsPa55w0rd',
                                    ]
                                ),
                            ],
                        ]
                    )
                );

                // ActorUsers::recordChangeEmailRequest
                $this->awsFixtures->append(
                    new Result(
                        [
                            'Item' => $this->marshalAwsResultData(
                                [
                                    'EmailResetExpiry' => time() + (60 * 60 * 48),
                                    'Email' => $this->userAccountEmail,
                                    'LastLogin' => null,
                                    'Id' => $this->userAccountId,
                                    'NewEmail' => $this->newEmail,
                                    'EmailResetToken' => $this->userEmailResetToken,
                                    'Password' => $this->userAccountPassword,
                                ]
                            ),
                        ]
                    )
                );
                break;
        }

        if (!str_contains($context, 'has expired')) {
            $userService = $this->container->get(UserService::class);

            try {
                $userService->requestChangeEmail(
                    $this->userAccountId,
                    $this->newEmail,
                    new HiddenString($this->userAccountPassword)
                );
            } catch (ConflictException $ex) {
                assertEquals(409, $ex->getCode());
                return;
            }

            throw new ExpectationFailedException('Conflict exception was not thrown');
        }
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword()
    {
        $password = 'inc0rr3cT';
        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        $userService = $this->container->get(UserService::class);

        try {
            $userService->requestChangeEmail($this->userAccountId, $this->newEmail, new HiddenString($password));
        } catch (ForbiddenException $ex) {
            assertEquals(403, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Forbidden exception was not thrown for incorrect password');
    }

    /**
     * @When /^I request to delete my account$/
     * @When /^I request to remove an LPA$/
     * @Then /^I cannot see my LPA on the dashboard$/
     * @Then /^I can see a flash message confirming that my LPA has been removed$/
     * @Then /^I confirm that I want to remove the LPA$/
     */
    public function iRequestToDeleteMyAccount()
    {
        // Not needed in this context
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail()
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
        $userService = $this->container->get(UserService::class);
        $response = $userService->requestChangeEmail(
            $this->userAccountId,
            $this->newEmail,
            new HiddenString($this->userAccountPassword)
        );

        assertEquals($this->userAccountId, $response['Id']);
        assertEquals($this->userAccountEmail, $response['Email']);
        assertEquals($this->newEmail, $response['NewEmail']);
        assertEquals($this->userAccountPassword, $response['Password']);
        assertEquals($this->userEmailResetToken, $response['EmailResetToken']);
        assertArrayHasKey('EmailResetExpiry', $response);
    }

    /**
     * @Given I want to create a new account
     */
    public function iWantToCreateANewAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^my account cannot be found$/
     */
    public function myAccountCannotBeFound()
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate('incorrect@email.com', $this->userAccountPassword);
        } catch (NotFoundException $ex) {
            assertEquals('User not found for email', $ex->getMessage());
            assertEquals(404, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected not found exception was not thrown');
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset()
    {
        // Not needed for this context
    }

    /**
     * @then my account is activated
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
        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::delete
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id' => $this->userAccountId,
                            'Email' => $this->userAccountEmail,
                            'Password' => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                            'LastLogin' => null,
                        ]
                    ),
                ]
            )
        );

        $userService = $this->container->get(UserService::class);

        $deletedUser = $userService->deleteUserAccount($this->userAccountId);

        assertEquals($this->userAccountId, $deletedUser['Id']);
        assertEquals($this->userAccountEmail, $deletedUser['Email']);
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid()
    {
        // Not needed for this context
    }

    /**
     * @Then my password has been associated with my user account
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        $command = $this->awsFixtures->getLastCommand();

        assertEquals('actor-users', $command['TableName']);
        assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        assertEquals('UpdateItem', $command->getName());
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }
}
