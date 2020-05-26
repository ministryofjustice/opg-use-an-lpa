<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\DataAccess\DynamoDb\UserLpaActorMap;
use App\DataAccess\DynamoDb\ViewerCodeActivity;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\GoneException;
use App\Exception\NotFoundException;
use App\Exception\UnauthorizedException;
use App\Service\ActorCodes\ActorCodeService;
use App\Service\Log\RequestTracing;
use App\Service\Lpa\LpaService;
use App\Service\User\UserService;
use Aws\DynamoDb\Marshaler;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use BehatTest\Context\SetupEnv;
use Common\Service\Lpa\ViewerCodeService;
use DateInterval;
use DateTimeZone;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property $userAccountId
 * @property $userAccountEmail
 * @property $userLpaActorToken
 * @property $passwordResetData
 * @property $userId
 * @property $actorLpaId
 * @property $passcode
 * @property $lpaUid
 * @property $userDob
 * @property $password
 * @property $lpa
 * @property $userAccountPassword
 * @property $userActivationToken
 * @property $actorAccountCreateData
 * @property $organisation
 * @property $accessCode
 * @property $newEmail
 * @property $userEmailResetToken
 */
class AccountContext extends BaseIntegrationContext
{
    use SetupEnv;

    /** @var MockHandler */
    private $apiFixtures;

    /** @var AwsMockHandler */
    private $awsFixtures;

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     * @Given /^I have added an LPA to my account$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = json_decode(file_get_contents(__DIR__ . '../../../../test/fixtures/example_lpa.json'));

        $this->passcode = 'XYUPHWQRECHV';
        $this->lpaUid = '700000000054';
        $this->userDob = '1975-10-05';
        $this->actorLpaId = 9;
        $this->userId = '9999999999';
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
        $this->password = 'pa33w0rd';
        $this->userAccountPassword = 'n3wPassWord';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'       => $this->userAccountId,
                    'Email'    => $this->userAccountEmail,
                    'Password' => password_hash($this->password, PASSWORD_DEFAULT),
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

        $us = $this->container->get(UserService::class);

        $user = $us->authenticate($this->userAccountEmail, $this->password);

        assertEquals($this->userAccountId, $user['Id']);
        assertEquals($this->userAccountEmail, $user['Email']);
    }

    /**
     * @When /^I enter incorrect login password$/
     */
    public function iEnterIncorrectLoginPassword()
    {
        // Not needed in this context
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

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate($this->userAccountEmail, '1nc0rr3ctPa33w0rd');
        } catch (ForbiddenException $fe){
            assertEquals('Authentication failed for email ' . $this->userAccountEmail, $fe->getMessage());
            assertEquals(403, $fe->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected forbidden exception was not thrown');
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

        $us = $this->container->get(UserService::class);

        try {
            $us->authenticate($this->userAccountEmail, $this->userAccountPassword);
        } catch (UnauthorizedException $ex) {
            assertEquals('Authentication attempted against inactive account with Id ' . $this->userAccountId, $ex->getMessage());
            assertEquals(401, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException('Expected unauthorized exception was not thrown');
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
        $resetToken = 'AAAABBBBCCCC';

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
                'PasswordResetToken'  => $resetToken,
                'PasswordResetExpiry' => time() + (60 * 60 * 24) // 24 hours in the future
            ])
        ]));

        $us = $this->container->get(UserService::class);

        $this->passwordResetData = $us->requestPasswordReset($this->userAccountEmail);
    }

    /**
     * @When I create an account
     */
    public function iCreateAnAccount()
    {
        $this->userAccountEmail = 'hello@test.com';
        $this->userAccountPassword = 'n3wPassWord';

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
                'Email' => $this->userAccountEmail,
                'ActivationToken' => '123456789'
            ])
        ]));

        $us = $this->container->get(UserService::class);

        $this->userActivationToken = $us->add([
            'email' => $this->userAccountEmail,
            'password' => $this->userAccountPassword
        ])['ActivationToken'] ;
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        assertArrayHasKey('PasswordResetToken', $this->passwordResetData);
    }

    /**
     * @Then I receive unique instructions on how to activate my account
     */
    public function iReceiveUniqueInstructionsOnHowToActivateMyAccount()
    {
        assertEquals('123456789', $this->userActivationToken);
    }

    /**
     * @Then I am informed about an existing account
     */
    public function iAmInformedAboutAnExistingAccount()
    {
        assertEquals('activate1234567890', $this->actorAccountCreateData['ActivationToken']);
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
        // ActorUsers::activate
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'    => $this->userAccountId,
                    'Email' => $this->userAccountEmail,

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

        $us = $this->container->get(UserService::class);

        $userId = $us->canResetPassword($this->passwordResetData['PasswordResetToken']);

        assertEquals($this->userAccountId, $userId);
    }

    /**
     * @When I choose a new password
     */
    public function iChooseANewPassword()
    {
        $password = 'newPass0rd';

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

        $us = $this->container->get(UserService::class);

        $us->completePasswordReset($this->passwordResetData['PasswordResetToken'], $password);
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

    /**
     * @When I follow my unique expired instructions on how to reset my password
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
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

        $us = $this->container->get(UserService::class);

        try {
            $userId = $us->canResetPassword($this->passwordResetData['PasswordResetToken']);
        } catch(GoneException $gex) {
            assertEquals('Reset token not found', $gex->getMessage());
        }
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
     * @Given I have asked to create a new account
     */
    public function iHaveAskedToCreateANewAccount()
    {
        $this->actorAccountCreateData = [
            'Id'                  => '123456789',
            'Email'               => 'hello@test.com',
            'Password'            => 'Pa33w0rd',
            'ActivationToken'     => 'activate1234567890',
            'ActivationTokenExpiry' => time() + (60 * 60 * 12) // 12 hours in the future
        ];
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
                    'Id'     => '1'
                ])
            ]
        ]));

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([]));

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id' => '123456789'
            ])
        ]));

        $us = $this->container->get(UserService::class);

        $userData = $us->activate($this->actorAccountCreateData['ActivationToken']);

        assertNotNull($userData);
    }

    /**
     * @then my account is activated
     */
    public function myAccountIsActivated()
    {
        // Not needed for this context
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

        $us = $this->container->get(UserService::class);
        try {
            $userData = $us->activate($this->actorAccountCreateData['ActivationToken']);
        }
        catch (\Exception $ex) {
            assertEquals('User not found for token', $ex->getMessage());
        }
    }

    /**
     * @Then I am told my unique instructions to activate my account have expired
     */
    public function iAmToldMyUniqueInstructionsToActivateMyAccountHaveExpired()
    {
        // Not used in this context
    }

    /**
     * @When I create an account using duplicate details
     */
    public function iCreateAnAccountUsingDuplicateDetails()
    {
        $actorAccountCreateData = [
            'email' => 'hello@test.com',
            'password' => 'n3wPassWord',
            'activationToken' => 'activate1234567890'
        ];

        // ActorUsers::activate
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'AccountActivationToken'  => $actorAccountCreateData['activationToken'] ,
                    'Email' => $actorAccountCreateData['email'],
                    'Password' => $actorAccountCreateData['password']
                ])
            ]
        ]));

        // ActorUsers::add
        $this->awsFixtures->append(new Result());

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Items'  =>[
                $this->marshalAwsResultData([
                    'AccountActivationToken'  => $actorAccountCreateData['activationToken'],
                    'Email' => $actorAccountCreateData['email']
                ])
            ]
        ]));

        $us = $this->container->get(UserService::class);

        try {
            $us->add([
                'email' => $actorAccountCreateData['email'],
                'password' => $actorAccountCreateData['password']
            ]);
        }catch(\Exception $ex) {
            assertContains('User already exists with email address' . ' ' . $actorAccountCreateData['email'], $ex->getMessage());
        }
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
                    'Active'    => true,
                    'Expires'   => '2021-09-25T00:00:00Z',
                    'ActorCode' => $this->passcode,
                    'ActorLpaId'=> $this->actorLpaId,
                ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->passcode, $this->lpaUid, $this->userDob);

        assertEquals($validatedLpa['lpa']['uId'], $this->lpaUid);

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
        $now = (new DateTime)->format('Y-m-d\TH:i:s.u\Z');
        $this->userLpaActorToken = '13579';

        // ActorCodes::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid' => $this->lpaUid,
                'Active'    => true,
                'Expires'   => '2021-09-25T00:00:00Z',
                'ActorCode' => $this->passcode,
                'ActorLpaId'=> $this->actorLpaId,
            ])
        ]));

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($this->lpa)
                )
            );

        // UserLpaActorMap::create
        $this->awsFixtures->append(new Result([
            'Item' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->userLpaActorToken,
                    'UserId'    => $this->userId,
                    'SiriusUid' => $this->lpaUid,
                    'ActorId'   => $this->actorLpaId,
                    'Added'     => $now,
                ])
            ]
        ]));

        // ActorCodes::flagCodeAsUsed
        $this->awsFixtures->append(new Result([]));

        $actorCodeService = $this->container->get(ActorCodeService::class);

        try {
            $response = $actorCodeService->confirmDetails($this->passcode, $this->lpaUid, $this->userDob, (string) $this->actorLpaId);
        } catch (Exception $ex) {
            throw new Exception('Lpa confirmation unsuccessful');
        }

        assertNotNull($response);
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
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->passcode, $this->lpaUid, $this->userDob);

        assertNull($validatedLpa);
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
        $lpaService = $this->container->get(LpaService::class);

        $lpas = $lpaService->getAllForUser($this->userId);

        assertEmpty($lpas);
    }

    /**
     * @Given /^I am on the dashboard page$/
     */
    public function iAmOnTheDashboardPage()
    {
        // Not needed for this context
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
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // LpaRepository::get
        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->lpaUid)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode($this->lpa)));

        // LpaService::getLpaById
        $this->apiFixtures->get('/v1/lpas/' . $this->userLpaActorToken, ['user-token' => $this->userId])
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $this->lpa])
                ));

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string) $this->userId);

        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->lpa->id, $lpaData['lpa']['id']);
        assertEquals($this->lpa->status, $lpaData['lpa']['status']);
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
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::add
        $this->awsFixtures->append(new Result());
    }

    /**
     * @Then /^I am given a unique access code$/
     */
    public function iAmGivenAUniqueAccessCode()
    {
        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codeData = $viewerCodeService->addCode($this->userLpaActorToken, $this->userId, $this->organisation);

        $codeExpiry = (new DateTime($codeData['expires']))->format('Y-m-d');
        $in30Days = (new DateTime('23:59:59 +30 days', new DateTimeZone('Europe/London')))->format('Y-m-d');

        assertArrayHasKey('code', $codeData);
        assertNotNull($codeData['code']);
        assertEquals($codeExpiry, $in30Days);
        assertEquals($codeData['organisation'], $this->organisation);
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
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string) $this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['id']);
        assertEquals($this->lpaUid, $lpaData['actor']['details']['uId']);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData(
                    [
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
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);

        // actor id  does not match the userId returned

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Added'], '2021-01-05 12:34:56');
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
        // Not needed for this context
    }

    /**
     * @Then /^I want to be asked for confirmation prior to cancellation/
     */
    public function iWantToBeAskedForConfirmationPriorToCancellation()
    {
        // Not needed for this context
    }

    /**
     * @When /^I confirm cancellation of the chosen viewer code/
     */
    public function iConfirmCancellationOfTheChosenViewerCode()
    {
        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::get
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'SiriusUid'        => $this->lpaUid,
                    'Added'            => '2020-01-05 12:34:56',
                    'Expires'          => '2021-01-05 12:34:56',
                    'Cancelled'        => '2020-01-15',
                    'UserLpaActor'     => $this->userLpaActorToken,
                    'Organisation'     => $this->organisation,
                    'ViewerCode'       => $this->accessCode
                ])
            ]
        ]));

        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codeData = $viewerCodeService->cancelCode($this->userLpaActorToken, $this->userId, $this->accessCode);

        assertEmpty($codeData);

        //Back to access code to get LPA details
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string) $this->userId);

        // Get the share codes

        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData(
                    [
                        'SiriusUid'        => $this->lpaUid,
                        'Added'            => '2020-01-05',
                        'Expires'          => '2021-01-05',
                        'Cancelled'        => '2020-01-15',
                        'UserLpaActor'     => $this->userLpaActorToken,
                        'Organisation'     => $this->organisation,
                        'ViewerCode'       => $this->accessCode,
                    ])
            ]
        ]));

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());
        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertArrayHasKey('Cancelled', $accessCodes[0]);

        assertGreaterThan(strtotime($accessCodes[0]['Cancelled']), strtotime($accessCodes[0]['Expires']));
        assertGreaterThan(strtotime($accessCodes[0]['Cancelled']), strtotime((new DateTime('now'))->format('Y-m-d')));
    }

    /**
    * @Then /^I should be shown the details of the cancelled viewer code with cancelled status/
    */
     public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithCancelledStatus()
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
                'ActorCode' => $this->passcode,
                'ActorLpaId' => $this->actorLpaId,
            ])
        ]));

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $response = $actorCodeService->validateDetails($this->passcode, $this->lpaUid, $this->userDob);

        assertNull($response);
    }

    /**
     * @Then /^The LPA should not be found$/
     */
    public function theLPAShouldNotBeFound()
    {
        // Not needed for this context
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

    /**
     * @When /^One of the generated access code has expired$/
     */
    public function oneOfTheGeneratedAccessCodeHasExpired()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I should be shown the details of the viewer code with status(.*)/
     */
    public function iShouldBeShownTheDetailsOfTheCancelledViewerCodeWithStatus()
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
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string) $this->userId);

        assertArrayHasKey('date', $lpaData);
        assertArrayHasKey('actor', $lpaData);
        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->actorLpaId, $lpaData['actor']['details']['id']);
        assertEquals($this->lpaUid, $lpaData['actor']['details']['uId']);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData(
                    [
                        'SiriusUid'        => $this->lpaUid,
                        'Added'            => '2021-01-05 12:34:56',
                        'Expires'          => '2021-01-05 12:34:56',
                        'UserLpaActor'     => $this->userLpaActorToken,
                        'Organisation'     => $this->organisation,
                        'ViewerCode'       => $this->accessCode
                    ])
            ]
        ]));

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);

        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertArrayHasKey('ViewerCode', $accessCodes[0]);
        assertArrayHasKey('Expires', $accessCodes[0]);
        assertEquals($accessCodes[0]['Organisation'], $this->organisation);
        assertEquals($accessCodes[0]['SiriusUid'], $this->lpaUid);
        assertEquals($accessCodes[0]['UserLpaActor'], $this->userLpaActorToken);
        assertEquals($accessCodes[0]['Expires'], '2021-01-05 12:34:56');
    }

    /**
     * @When /^I do not confirm cancellation of the chosen viewer code/
     */
    public function iDoNotConfirmCancellationOfTheChosenViewerCode()
    {
        // Not needed for this context
    }

    /**
     * @When /^I check my access codes$/
     */
    public function iCheckMyAccessCodes()
    {
        //Get the LPA

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);

        $lpaData = $lpaService->getByUserLpaActorToken($this->userLpaActorToken, (string) $this->userId);

        assertEquals($this->userLpaActorToken, $lpaData['user-lpa-actor-token']);
        assertEquals($this->lpa->uId, $lpaData['lpa']['uId']);
        assertEquals($this->lpaUid, $lpaData['actor']['details']['uId']);

        // Get the share codes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodes::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result([]));

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $accessCodes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertEmpty($accessCodes);
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
     * @Given /^I have 2 codes for one of my LPAs$/
     */
    public function iHave2CodesForOneOfMyLPAs()
    {
        $this->iHaveCreatedAnAccessCode();
        $this->iHaveCreatedAnAccessCode();
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
                    'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);
        $lpa = $lpaService->getAllForUser($this->userId);

        assertArrayHasKey($this->userLpaActorToken, $lpa);
        assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken );
        assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId );
        assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid );

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
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

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertCount(2, $codes);
        assertEquals($codes[0], $code1);
        assertEquals($codes[1], $code2);

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        // This response is duplicated for the 2nd code

        // ViewerCodeActivity::getStatusesForViewerCodes
        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(ViewerCodeActivity::class);
        $codesWithStatuses = $viewerCodeService->getStatusesForViewerCodes($codes);

        // Loop for asserting on both the 2 codes returned
        for ($i=0; $i < 2; $i++) {
            assertCount(2, $codesWithStatuses);
            assertEquals($codesWithStatuses[$i]['SiriusUid'], $this->lpaUid);
            assertEquals($codesWithStatuses[$i]['UserLpaActor'], $this->userLpaActorToken);
            assertEquals($codesWithStatuses[$i]['Organisation'], $this->organisation);
            assertEquals($codesWithStatuses[$i]['ViewerCode'], $this->accessCode);

            if ($i == 0) {
                assertEquals($codesWithStatuses[$i]['Expires'], $code1Expiry);
            } else {
                assertEquals($codesWithStatuses[$i]['Expires'], $code2Expiry);
            }
        }

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        $userLpaActorMap = $this->container->get(UserLpaActorMap::class);
        $lpa = $userLpaActorMap->get($this->userLpaActorToken);

        assertEquals($lpa['SiriusUid'], $this->lpaUid);
        assertEquals($lpa['Id'], $this->userLpaActorToken);
        assertEquals($lpa['ActorId'], $this->actorLpaId);
        assertEquals($lpa['UserId'], $this->userId);
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
                    'ActorId'          => $this->actorLpaId,
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

        $lpaService = $this->container->get(LpaService::class);
        $lpa = $lpaService->getAllForUser($this->userId);

        assertArrayHasKey($this->userLpaActorToken, $lpa);
        assertEquals($lpa[$this->userLpaActorToken]['user-lpa-actor-token'], $this->userLpaActorToken );
        assertEquals($lpa[$this->userLpaActorToken]['lpa']['uId'], $this->lpa->uId );
        assertEquals($lpa[$this->userLpaActorToken]['actor']['details']['uId'], $this->lpaUid );

        //ViewerCodeService:getShareCodes

        // UserLpaActorMap::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'SiriusUid'        => $this->lpaUid,
                'Added'            => (new DateTime('2020-01-01'))->format('Y-m-d\TH:i:s.u\Z'),
                'Id'               => $this->userLpaActorToken,
                'ActorId'          => $this->actorLpaId,
                'UserId'           => $this->userId
            ])
        ]));

        // ViewerCodesRepository::getCodesByUserLpaActorId
        $this->awsFixtures->append(new Result());

        $viewerCodeService = $this->container->get(\App\Service\ViewerCodes\ViewerCodeService::class);
        $codes = $viewerCodeService->getCodes($this->userLpaActorToken, $this->userId);

        assertEmpty($codes);
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

        $us = $this->container->get(UserService::class);

        $us->completeChangePassword($this->userAccountId, $this->userAccountPassword, $newPassword);

        $command = $this->awsFixtures->getLastCommand();

        assertEquals('actor-users', $command['TableName']);
        assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        assertEquals('UpdateItem', $command->getName());
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

        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->userAccountId,
                'Password' => password_hash($failedPassword, PASSWORD_DEFAULT)
            ])
        ]));

        // ActorUsers::resetPassword
        $this->awsFixtures->append(new Result([]));

        $us = $this->container->get(UserService::class);

        $us->completeChangePassword($this->userAccountId, $failedPassword, $newPassword);

        $command = $this->awsFixtures->getLastCommand();

        assertEquals('actor-users', $command['TableName']);
        assertEquals($this->userAccountId, $command['Key']['Id']['S']);
        assertEquals('UpdateItem', $command->getName());
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
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'        => $this->userAccountId,
                'Email'     => $this->userAccountEmail,
                'Password'  => password_hash($this->userAccountPassword, PASSWORD_DEFAULT),
                'LastLogin' => null
            ])
        ]));

        $userService = $this->container->get(UserService::class);

        $deletedUser = $userService->deleteUserAccount($this->userAccountId);

        assertEquals($this->userAccountId, $deletedUser['Id']);
        assertEquals($this->userAccountEmail, $deletedUser['Email']);
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

        $userService = $this->container->get(UserService::class);

        try {
            $userService->requestChangeEmail($this->userAccountId, $this->newEmail, 'inc0rr3cT');
        } catch (ForbiddenException $ex) {
            assertEquals(403, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException("Forbidden exception was not thrown for incorrect password");
    }

    /**
     * @Then /^I should be told that I could not change my email because my password is incorrect$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailBecauseMyPasswordIsIncorrect()
    {
        // Not needed for this context
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

        $userService = $this->container->get(UserService::class);

        try {
            $userService->requestChangeEmail($this->userAccountId, $this->newEmail, $this->userAccountPassword);
        } catch (ConflictException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException("Conflict exception was not thrown");
    }

    /**
     * @Then /^I should be told that I could not change my email as their was a problem with the request$/
     */
    public function iShouldBeToldThatICouldNotChangeMyEmailAsTheirWasAProblemWithTheRequest()
    {
        // Not needed for this context
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

        $userService = $this->container->get(UserService::class);

        try {
            $userService->requestChangeEmail($this->userAccountId, $this->newEmail, $this->userAccountPassword);
        } catch (ConflictException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException("Conflict exception was not thrown");
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
        $userService = $this->container->get(UserService::class);
        $response = $userService->requestChangeEmail($this->userAccountId, $this->newEmail, $this->userAccountPassword);

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

        $userService = $this->container->get(UserService::class);

        $userId = $userService->canResetEmail($this->userEmailResetToken);

        assertEquals($this->userAccountId, $userId);

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

        $reset = $userService->completeChangeEmail($this->userEmailResetToken);

        assertNull($reset);
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

        $userService = $this->container->get(UserService::class);

        try {
            $userService->canResetEmail($this->userEmailResetToken);
        } catch (GoneException $ex) {
            assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException();
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

        $userService = $this->container->get(UserService::class);

        try {
            $userService->canResetEmail($this->userEmailResetToken);
        } catch (GoneException $ex) {
            assertEquals(410, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException();
    }

    /**
     * @When /^I create an account using with an email address that has been requested for reset$/
     */
    public function iCreateAnAccountUsingWithAnEmailAddressThatHasBeenRequestedForReset()
    {
        $userAccountCreateData = [
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

        $us = $this->container->get(UserService::class);

        try {
            $us->add(['email' => $userAccountCreateData['Email'], 'password' => $userAccountCreateData['Password']]);
        } catch (ConflictException $ex) {
            assertEquals(409, $ex->getCode());
            return;
        }

        throw new ExpectationFailedException();
    }

    /**
     * @Then /^I am informed that there was a problem with that email address$/
     */
    public function iAmInformedThatThereWasAProblemWithThatEmailAddress()
    {
        // Not needed for this context
    }
}
