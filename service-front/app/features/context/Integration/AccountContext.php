<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use BehatTest\Context\ActorContextTrait;
use BehatTest\Context\ContextUtilities;
use Common\Exception\ApiException;
use Common\Service\Log\RequestTracing;
use Common\Service\Lpa\LpaFactory;
use Common\Service\Lpa\LpaService;
use Common\Service\Lpa\ViewerCodeService;
use Common\Service\Notify\NotifyService;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use ParagonIE\HiddenString\HiddenString;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 **/
class AccountContext extends BaseIntegrationContext
{
    use ActorContextTrait;

    private const LPA_SERVICE_GET_LPAS        = 'LpaService::getLpas';
    private const USER_SERVICE_DELETE_ACCOUNT = 'UserService::deleteAccount';

    private string $userEmail;
    private string $userIdentity;

    #[Given('/^I am a user of the lpa application$/')]
    public function iAmAUserOfTheLpaApplication(): void
    {
        $this->userEmail = 'test@example.com';
    }

    #[Given('/^I am currently signed in$/')]
    public function iAmCurrentlySignedIn(): void
    {
        $this->userEmail    = 'test@test.com';
        $this->userIdentity = '123';
    }

    #[Given('/^I am logged out of the service and taken to the deleted account confirmation page$/')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheDeletedAccountConfirmationPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the dashboard page$/')]
    public function iAmOnTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Given('/^I am on the settings page$/')]
    public function iAmOnTheSettingsPage(): void
    {
        //Not needed for this context
    }

    #[Then('/^I am taken back to the dashboard page$/')]
    public function iAmTakenBackToTheDashboardPage(): void
    {
        // Not needed for this context
    }

    #[Then('/^I am taken to the dashboard page$/')]
    public function iAmTakenToTheDashboardPage(): void
    {
        // API call for finding all the users added LPAs on dashboard
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $lpas = $this->container->get(LpaService::class)->getLpas($this->userIdentity);

        Assert::assertEmpty($lpas);
    }

    #[When('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed for this context
    }

    #[When('/^I fill in the form and click the cancel button$/')]
    public function iFillInTheFormAndClickTheCancelButton(): void
    {
        // API call for finding all the users added LPAs
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode([]),
                self::LPA_SERVICE_GET_LPAS
            )
        );

        $lpas = $this->container->get(LpaService::class)->getLpas($this->userIdentity);

        Assert::assertEmpty($lpas);
    }

    #[When('/^I request to delete my account$/')]
    public function iRequestToDeleteMyAccount(): void
    {
        // Not needed for this context
    }

    #[Given('/^I request to go back and try again$/')]
    public function iRequestToGoBackAndTryAgain(): void
    {
        // Not needed for this context
    }

    #[When('/^I view my user details$/')]
    public function iViewMyUserDetails(): void
    {
        // Not needed for this context
    }

    #[Then('/^My account is deleted$/')]
    public function myAccountIsDeleted(): void
    {
        // API call for deleting a user account
        $this->apiFixtures->append(
            ContextUtilities::newResponse(
                StatusCodeInterface::STATUS_OK,
                json_encode(
                    [
                        'Id'        => $this->userIdentity,
                        'Email'     => $this->userEmail,
                        'LastLogin' => null,
                    ]
                ),
                self::USER_SERVICE_DELETE_ACCOUNT
            )
        );

        $delete = $this->container->get(UserService::class)->deleteAccount($this->userIdentity);
        Assert::assertNull($delete);

        $request = $this->apiFixtures->getLastRequest();
        $uri     = $request->getUri()->getPath();

        Assert::assertEquals($uri, '/v1/delete-account/123');
    }

    protected function prepareContext(): void
    {
        // This is populated into the container using a Middleware which these integration
        // tests wouldn't normally touch but the container expects
        $this->container->set(RequestTracing::TRACE_PARAMETER_NAME, 'Root=1-1-11');

        // DO NOT use this method as below to create context global services out of the container
        // it breaks feature flag testing.
        // $this->lpaService = $this->container->get(LpaService::class); // DONT DO THIS

        // $apiFixtures and $awsFixtures are the exception
        $this->apiFixtures = $this->container->get(MockHandler::class);
    }
}
