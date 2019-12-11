<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use App\Service\User\UserService;
use Aws\DynamoDb\Marshaler;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Behat\Context\Context;
use BehatTest\Context\SetupEnvTrait;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Class AccountContext
 *
 * @package BehatTest\Context\Integration
 *
 * @property $userAccountEmail
 * @property $passwordResetData
 */
class AccountContext implements Context, Psr11AwareContext
{
    use SetupEnvTrait;

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
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userAccountEmail = 'test@example.com';
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
                    'Id'    => '123456789',
                    'Email' => $this->userAccountEmail
                ])
            ]
        ]));

        // ActorUsers::requestPasswordReset
        $this->awsFixtures->append(new Result([
            'Attributes' => $this->marshalAwsResultData([
                'Id'    => '123456789',
                'PasswordResetToken' => $resetToken
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