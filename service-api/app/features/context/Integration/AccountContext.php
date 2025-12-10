<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use App\Service\Log\RequestTracing;
use App\Service\User\UserService;
use Aws\MockHandler as AwsMockHandler;
use Aws\Result;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\SetupEnv;
use PHPUnit\Framework\Assert;

class AccountContext extends BaseIntegrationContext
{
    use SetupEnv;

    private AwsMockHandler $awsFixtures;
    private array $createUserResponse;
    public string $userAccountId;
    public string $userAccountEmail;

    #[Given('/^I access the login form$/')]
    public function iAccessTheLoginForm(): void
    {
        // Not needed in this context
    }

    #[Given('I am a user of the lpa application')]
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userAccountId    = '123456789';
        $this->userAccountEmail = 'test@example.com';
    }

    #[Given('I am currently signed in')]
    #[Then('I am signed in')]
    public function iAmCurrentlySignedIn(): void
    {
        // Not needed in this context
    }

    #[Given('I am logged out of the service and taken to the index page')]
    #[Given('I am logged out of the service and taken to the deleted account confirmation page')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage(): void
    {
        // Not needed in this context
    }

    #[Given('I am not a user of the lpa application')]
    public function iAmNotaUserOftheLpaApplication(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am taken back to the dashboard page$/')]
    #[Given('/^I am on the dashboard page$/')]
    #[Given('/^I am on the user dashboard page$/')]
    #[Then('/^I cannot see the added LPA$/')]
    public function iAmOnTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the settings page$/')]
    public function iAmOnTheSettingsPage(): void
    {
        // Not needed in this context
    }

    #[Then('/^I cannot see my access codes and their details$/')]
    public function iAmTakenBackToTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    #[When('/^I request to delete my account$/')]
    #[When('/^I request to remove an LPA$/')]
    #[Then('/^I cannot see my LPA on the dashboard$/')]
    #[Then('/^I can see a flash message confirming that my LPA has been removed$/')]
    #[Then('/^I confirm that I want to remove the LPA$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(
            new Result(
                [
                    'Item' => $this->marshalAwsResultData(
                        [
                            'Id'       => $this->userAccountId,
                            'Email'    => $this->userAccountEmail,
                            'Identity' => 'identity',
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
                            'Id'       => $this->userAccountId,
                            'Email'    => $this->userAccountEmail,
                            'Identity' => 'identity',
                        ]
                    ),
                ]
            )
        );

        // ActorUsers::delete
        $this->awsFixtures->append(new Result([]));

        $userService = $this->container->get(UserService::class);

        $deletedUser = $userService->deleteUserAccount($this->userAccountId);

        Assert::assertEquals($this->userAccountId, $deletedUser['Id']);
        Assert::assertEquals($this->userAccountEmail, $deletedUser['Email']);
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        $this->awsFixtures = $this->container->get(AwsMockHandler::class);
    }
}
