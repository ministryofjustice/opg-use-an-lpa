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

class AccountContext implements Context
{
    use BaseAcceptanceContextTrait;
    use SetupEnv;

    #[Given('I am currently signed in')]
    public function iAmCurrentlySignedIn(): void
    {
        // Not needed in this context
    }

    #[Given('/^I confirm that I want to delete my account$/')]
    public function iConfirmThatIWantToDeleteMyAccount(): void
    {
        // Not needed in this context
    }

    #[Then('I request to delete my account')]
    public function iRequestToDeleteMyAccount(): void
    {
        // ActorUsers::get
        $this->awsFixtures->append(new Result([
            'Item' => $this->marshalAwsResultData([
                'Id'       => $this->base->userAccountId,
                'Email'    => $this->base->userAccountEmail,
                'Password' => password_hash($this->base->userAccountPassword, PASSWORD_DEFAULT, ['cost' => 13]),
            ]),
        ]));

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
    }

    #[Then('My account is deleted')]
    public function myAccountIsDeleted(): void
    {
        $this->ui->assertSession()->statusCodeEquals(StatusCodeInterface::STATUS_OK);
    }

    #[Given('I am logged out of the service and taken to the deleted account confirmation page')]
    public function iAmLoggedOutOfTheServiceAndTakenToTheIndexPage(): void
    {
        // Not needed in this context
    }

    #[When('/^I do not confirm cancellation of the chosen viewer code/')]
    #[When('/^I request to return to the dashboard page/')]
    public function iDoNotConfirmCancellationOfTheChosenViewerCode(): void
    {
        // Not needed for this context
    }

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
