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
use PHPUnit\Framework\Assert;
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
    public function iAccessTheLoginForm(): void
    {
        // Not needed in this context
    }

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userAccountId = '123456789';
        $this->userAccountEmail = 'test@example.com';
        $this->userAccountPassword = 'pa33w0rd';
    }

    /**
     * @Given I am currently signed in
     * @Then /^I am signed in$/
     */
    public function iAmCurrentlySignedIn(): void
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

        $user = $us->authenticate($this->userAccountEmail, new HiddenString($this->password));

        Assert::assertEquals($this->userAccountId, $user['Id']);
        Assert::assertEquals($this->userAccountEmail, $user['Email']);
    }

    /**
     * @Then I am informed about an existing account
     * @Then I send the activation email again
     */
    public function iAmInformedAboutAnExistingAccount(): void
    {
        Assert::assertEquals('activate1234567890', $this->actorAccountCreateData['ActivationToken']);
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am logged out of the service and taken to the index page$/
     */
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage(): void
    {
        // Not needed in this context
    }

    /**
     * @Given I am not a user of the lpa application
     */
    public function iAmNotaUserOftheLpaApplication(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the change email page$/
     */
    public function iAmOnTheChangeEmailPage(): void
    {
        $this->newEmail = 'newEmail@test.com';
        $this->userEmailResetToken = '12345abcde';
    }

    /**
     * @Given /^I am on the dashboard page$/
     * @Given /^I am on the user dashboard page$/
     * @Then /^I cannot see the added LPA$/
     */
    public function iAmOnTheDashboardPage(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the your details page$/
     */
    public function iAmOnTheYourDetailsPage(): void
    {
        // Not needed in this context
    }

    /**
     * @Then /^I am taken back to the dashboard page$/
     * @Then /^I cannot see my access codes and their details$/
     */
    public function iAmTakenBackToTheDashboardPage(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told my account has not been activated$/
     */
    public function iAmToldMyAccountHasNotBeenActivated(): void
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
            $us->authenticate($this->userAccountEmail, new HiddenString($this->userAccountPassword));
        } catch (UnauthorizedException $ex) {
            Assert::assertEquals(
                'Authentication attempted against inactive account with Id ' . $this->userAccountId,
                $ex->getMessage()
            );
            Assert::assertEquals(401, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected unauthorized exception was not thrown');
    }

    /**
     * @Then /^I am told my credentials are incorrect$/
     */
    public function iAmToldMyCredentialsAreIncorrect(): void
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
            $us->authenticate($this->userAccountEmail, new HiddenString('1nc0rr3ctPa33w0rd'));
        } catch (ForbiddenException $fe) {
            Assert::assertEquals('Authentication failed for email ' . $this->userAccountEmail, $fe->getMessage());
            Assert::assertEquals(403, $fe->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected forbidden exception was not thrown');
    }

    /**
     * @Then /^I am told my current password is incorrect$/
     */
    public function iAmToldMyCurrentPasswordIsIncorrect(): void
    {
        // Not needed in this context
    }

    /**
     * @Then /^I am told my password was changed$/
     */
    public function iAmToldMyPasswordWasChanged(): void
    {
        // Not needed for this context
    }

    /**
     * @Then I am told my unique instructions to activate my account have expired
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired(): void
    {
        // Not used in this context
    }

    /**
     * @Then I am told that my instructions have expired
     */
    public function iAmToldThatMyInstructionsHaveExpired(): void
    {
        // Not used in this context
    }

    /**
     * @Then I am unable to continue to reset my password
     */
    public function iAmUnableToContinueToResetMyPassword(): void
    {
        // Not used in this context
    }

    /**
     * @When I ask for my password to be reset
     */
    public function iAskForMyPasswordToBeReset(): void
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
    public function iAskToChangeMyPassword(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I cannot enter my current password$/
     */
    public function iCannotEnterMyCurrentPassword(): void
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

        Assert::assertEquals('actor-users', $command['TableName']);
        Assert::assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        Assert::assertEquals('UpdateItem', $command->getName());
    }

    /**
     * @When I choose a new password
     */
    public function iChooseANewPassword(): void
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
    public function iClickAnOldLinkToVerifyMyNewEmailAddressContainingATokenThatNoLongerExists(): void
    {
        // ActorUsers::getIdByEmailResetToken
        $this->awsFixtures->append(new Result([]));

        $userService = $this->container->get(UserService::class);

        try {
            $userService->canResetEmail($this->userEmailResetToken);
        } catch (GoneException $ex) {
            Assert::assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When /^I click the link to verify my new email address$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddress(): void
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

        Assert::assertEquals($this->userAccountId, $userId);

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

        Assert::assertNull($reset);
    }

    /**
     * @When /^I click the link to verify my new email address after my token has expired$/
     */
    public function iClickTheLinkToVerifyMyNewEmailAddressAfterMyTokenHasExpired(): void
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
            Assert::assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @Given /^I confirm that I want to delete my account$/
     */
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    /**
     * @When I create an account
     */
    public function iCreateAnAccount(): void
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
    public function iCreateAnAccountUsingDuplicateDetails(): void
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
            Assert::assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When I create an account using duplicate details not yet activated
     */
    public function iCreateAnAccountUsingDuplicateDetailsNotActivated(): void
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
        Assert::assertEquals($result['Email'], $userAccountCreateData['Email']);
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset(): void
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
                                'Id' => '123456789',
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
            Assert::assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('');
    }

    /**
     * @When /^I enter correct credentials$/
     */
    public function iEnterCorrectCredentials(): void
    {
        // Not needed in this context
    }

    /**
     * @When /^I enter incorrect login email$/
     */
    public function iEnterIncorrectLoginEmail(): void
    {
        // Not needed in this context
    }

    /**
     * @When /^I enter incorrect login password$/
     */
    public function iEnterIncorrectLoginPassword(): void
    {
        // Not needed in this context
    }

    /**
     * @When I follow my instructions on how to activate my account after 24 hours
     */
    public function iFollowMyInstructionsOnHowToActivateMyAccountAfter24Hours(): void
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
            Assert::assertEquals('User not found for token', $ex->getMessage());
        }
    }

    /**
     * @When I follow my unique expired instructions on how to reset my password
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword(): void
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
            Assert::assertEquals('Reset token not found', $gex->getMessage());
        }
    }

    /**
     * @When I follow my unique instructions on how to reset my password
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword(): void
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

        Assert::assertEquals($this->userAccountId, $userId);
    }

    /**
     * @When I follow the instructions on how to activate my account
     */
    public function iFollowTheInstructionsOnHowToActivateMyAccount(): void
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

        Assert::assertNotNull($userData);
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     * @When /^I request to return to the dashboard page/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode(): void
    {
        // Not needed for this context
    }

    /**
     * @Given I have asked for my password to be reset
     */
    public function iHaveAskedForMyPasswordToBeReset(): void
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
    public function iHaveAskedToCreateANewAccount(): void
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
    public function iHaveForgottenMyPassword(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have not activated my account$/
     */
    public function iHaveNotActivatedMyAccount(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I have requested to change my email address$/
     */
    public function iHaveRequestedToChangeMyEmailAddress(): void
    {
        $this->userEmailResetToken = '12345abcde';
        $this->newEmail = 'newEmail@test.com';
    }

    /**
     * @Given /^I provide my current password$/
     */
    public function iProvideMyCurrentPassword(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I provide my new password$/
     */
    public function iProvideMyNewPassword(): void
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

        Assert::assertEquals('actor-users', $command['TableName']);
        Assert::assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        Assert::assertEquals('UpdateItem', $command->getName());
    }

    /**
     * @Then I receive unique instructions on how to activate my account
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount(): void
    {
        Assert::assertEquals('123456789', $this->userActivationToken);
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword(): void
    {
        Assert::assertArrayHasKey('PasswordResetToken', $this->passwordResetData);
    }

    /**
     * @When /^I request to change my email to a unique email address$/
     */
    public function iRequestToChangeMyEmailToAUniqueEmailAddress(): void
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
                Assert::assertEquals(409, $ex->getCode());
                return;
            }

            throw new ExpectationFailedException('Conflict exception was not thrown');
        }
    }

    /**
     * @When /^I request to change my email with an incorrect password$/
     */
    public function iRequestToChangeMyEmailWithAnIncorrectPassword(): void
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
            Assert::assertEquals(403, $ex->getCode());
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
    public function iRequestToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    /**
     * @Given /^I should be able to login with my new email address$/
     */
    public function iShouldBeAbleToLoginWithMyNewEmailAddress(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be sent an email to both my current and new email$/
     */
    public function iShouldBeSentAnEmailToBothMyCurrentAndNewEmail(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told my email change request was successful$/
     */
    public function iShouldBeToldMyEmailChangeRequestWasSuccessful(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be told that my email could not be changed$/
     */
    public function iShouldBeToldThatMyEmailCouldNotBeChanged(): void
    {
        // Not needed for this context
    }

    /**
     * @Given /^I should be told that my request was successful$/
     */
    public function iShouldBeToldThatMyRequestWasSuccessful(): void
    {
        $userService = $this->container->get(UserService::class);
        $response = $userService->requestChangeEmail(
            $this->userAccountId,
            $this->newEmail,
            new HiddenString($this->userAccountPassword)
        );

        Assert::assertEquals($this->userAccountId, $response['Id']);
        Assert::assertEquals($this->userAccountEmail, $response['Email']);
        Assert::assertEquals($this->newEmail, $response['NewEmail']);
        Assert::assertEquals($this->userAccountPassword, $response['Password']);
        Assert::assertEquals($this->userEmailResetToken, $response['EmailResetToken']);
        Assert::assertArrayHasKey('EmailResetExpiry', $response);
    }

    /**
     * @Given I want to create a new account
     */
    public function iWantToCreateANewAccount(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^my account cannot be found$/
     */
    public function myAccountCannotBeFound(): void
    {
        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate('incorrect@email.com', new HiddenString($this->userAccountPassword));
        } catch (NotFoundException $ex) {
            Assert::assertEquals('User not found for email', $ex->getMessage());
            Assert::assertEquals(404, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected not found exception was not thrown');
    }

    /**
     * @Then /^My account email address should be reset$/
     */
    public function myAccountEmailAddressShouldBeReset(): void
    {
        // Not needed for this context
    }

    /**
     * @then my account is activated
     */
    public function myAccountIsActivated(): void
    {
        // Not needed for this context
    }

    /**
     * @Then /^My account is deleted$/
     */
    public function myAccountIsDeleted(): void
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

        Assert::assertEquals($this->userAccountId, $deletedUser['Id']);
        Assert::assertEquals($this->userAccountEmail, $deletedUser['Email']);
    }

    /**
     * @Given /^My email reset token is still valid$/
     */
    public function myEmailResetTokenIsStillValid(): void
    {
        // Not needed for this context
    }

    /**
     * @Then my password has been associated with my user account
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount(): void
    {
        $command = $this->awsFixtures->getLastCommand();

        Assert::assertEquals('actor-users', $command['TableName']);
        Assert::assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        Assert::assertEquals('UpdateItem', $command->getName());
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }
}
