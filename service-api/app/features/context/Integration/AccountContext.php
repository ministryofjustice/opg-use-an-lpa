<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Exception\GoneException;
use App\Service\ActorCodes\ActorCodeService;
use Behat\Behat\Tester\Exception\PendingException;
use Acpr\Behat\Psr\Context\Psr11AwareContext;
use App\Service\User\UserService;
use Aws\DynamoDb\Marshaler;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\SetupEnv;
use App\Service\Lpa\LpaService;
use DateTime;
use Fig\Http\Message\StatusCodeInterface;
use JSHayes\FakeRequests\MockHandler;
use Exception;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property $userAccountId
 * @property $userAccountEmail
 * @property $passwordResetData
 * @property $userId
 * @property $actorLpaId
 * @property $passcode
 * @property $referenceNo
 * @property $userDob
 * @property $password
 * @property $lpa
 */
class AccountContext implements Context, Psr11AwareContext
{
    use SetupEnv;

    /** @var ContainerInterface */
    private $container;

    /** @var MockHandler */
    private $apiFixtures;

    /** @var AwsMockHandler */
    private $awsFixtures;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = file_get_contents(__DIR__ . '/../../../../../../opg-use-an-lpa/service-front/app/test/CommonTest/Service/Lpa/fixtures/full_example.json');

        $this->passcode = 'XYUPHWQRECHV';
        $this->referenceNo = '700000000054';
        $this->userDob = '1975-10-05';
        $this->actorLpaId = 0;
        $this->userId = '9999999999';
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
        $this->password = 'pa33w0rd';

        // ActorUsers::getByEmail
        $this->awsFixtures->append(new Result([
            'Items' => [
                $this->marshalAwsResultData([
                    'Id'       => $this->userAccountId,
                    'Email'    => $this->userAccountEmail,
                    'Password' => password_hash($this->password, PASSWORD_DEFAULT)
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
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        assertArrayHasKey('PasswordResetToken', $this->passwordResetData);
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
                    'ActorLpaId'=> $this->actorLpaId,
                ])
        ]));

        $lpaArray = json_decode($this->lpa);

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($lpaArray)
                )
            );

        $actorCodeService = $this->container->get(ActorCodeService::class);

        $validatedLpa = $actorCodeService->validateDetails($this->passcode, $this->referenceNo, $this->userDob);

        assertEquals($validatedLpa['lpa']['uId'], $this->referenceNo);

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
                'ActorLpaId'=> $this->actorLpaId,
            ])
        ]));

        $lpaArray = json_decode($this->lpa);

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode($lpaArray)
                )
            );

        // UserLpaActorMap::create
        $this->awsFixtures->append(new Result([
            'Item' => [
                $this->marshalAwsResultData([
                    'Id'        => $this->userAccountId,
                    'UserId'    => $this->userId,
                    'SiriusUid' => $this->referenceNo,
                    'ActorId'   => $this->actorLpaId,
                    'Added'     => $now,
                ])
            ]
        ]));

        // ActorCodes::flagCodeAsUsed
        $this->awsFixtures->append(new Result([]));

        $actorCodeService = $this->container->get(ActorCodeService::class);

        try {
            $response = $actorCodeService->confirmDetails($this->passcode, $this->referenceNo, $this->userDob, (string) $this->actorLpaId);
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

        $this->apiFixtures->get('/v1/use-an-lpa/lpas/' . $this->referenceNo)
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

        $validatedLpa = $actorCodeService->validateDetails($this->passcode, $this->referenceNo, $this->userDob);

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
