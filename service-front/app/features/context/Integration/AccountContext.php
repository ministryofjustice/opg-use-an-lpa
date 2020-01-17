<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Alphagov\Notifications\Client;
use Behat\Behat\Context\Context;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 *
 * @property string email
 * @property string resetToken
 */
class AccountContext implements Context, Psr11AwareContext
{
    /** @var ContainerInterface */
    private $container;

    /** @var MockHandler */
    private $apiFixtures;

    /** @var UserService */
    private $userService;

    /** @var EmailClient */
    private $emailClient;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->userService = $this->container->get(UserService::class);
        $this->emailClient = $this->container->get(EmailClient::class);
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->email = "test@example.com";
    }

    /**
     * @Given /^I have forgotten my password$/
     */
    public function iHaveForgottenMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When /^I ask for my password to be reset$/
     */
    public function iAskForMyPasswordToBeReset()
    {
        $this->resetToken = '1234567890';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([ 'PasswordResetToken' => $this->resetToken ])
                )
            );

        $token = $this->userService->requestPasswordReset($this->email);

        assertInternalType('string', $token);
        assertEquals($this->resetToken, $token);
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $expectedUrl = 'http://localhost/forgot-password/' . $this->resetToken;
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
                    use ($expectedUrl, $expectedTemplateId) {
                $requestBody = $request->getBody()->getContents();

                assertContains($this->resetToken, $requestBody);
                assertContains(json_encode($expectedUrl), $requestBody);
                assertContains($expectedTemplateId, $requestBody);
            });


        $this->emailClient->sendPasswordResetEmail($this->email, $expectedUrl);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->resetToken = '1234567890';
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->resetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->resetToken);
        assertTrue($canReset);
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        $this->apiFixtures->get('/v1/can-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_GONE))
            ->inspectRequest(function (RequestInterface $request, array $options) {
                $query = $request->getUri()->getQuery();
                assertContains($this->resetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->resetToken);
        assertFalse($canReset);
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $expectedPassword = 'newpassword';

        // API fixture for password reset
        $this->apiFixtures->patch('/v1/complete-password-reset')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([ 'Id' => '123456' ])))
            ->inspectRequest(function (RequestInterface $request, array $options) use ($expectedPassword) {
                $params = json_decode($request->getBody()->getContents(), true);

                assertInternalType('array', $params);
                assertEquals($this->resetToken, $params['token']);
                assertEquals($expectedPassword, $params['password']);
            });

        $this->userService->completePasswordReset($this->resetToken, $expectedPassword);
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that my instructions have expired$/
     */
    public function iAmToldThatMyInstructionsHaveExpired()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am unable to continue to reset my password$/
     */
    public function iAmUnableToContinueToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @Given /^I choose a new invalid password of "(.*)"$/
     */
    public function iChooseANewInvalid($password)
    {
        // Not needed for this context
    }

    /**
     * @Then /^I am told that my password is invalid because it needs at least (.*)$/
     */
    public function iAmToldThatMyPasswordIsInvalidBecauseItNeedsAtLeast($reason)
    {
        // Not needed for this context
    }

    /**
     * @Given /^I am on the add an LPA page$/
     */
    public function iAmOnTheAddAnLPAPage()
    {
        //throw new PendingException();
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        $expectedUrl = 'http://localhost/lpa/add-details';
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

        // API call for adding an LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
            use ($expectedUrl) {
                $requestBody = $request->getBody()->getContents();

                assertContains(json_encode($expectedUrl), $requestBody);
                //assertContains($expectedTemplateId, $requestBody);
            });


        //$this->emailClient->sendPasswordResetEmail($this->email, $expectedUrl);
    }

    /**
     * @Then /^My LPA is successfully added$/
     */
    public function myLPAIsSuccessfullyAdded()
    {
        //throw new PendingException();
    }

    /**
     * @Given /^My LPA appears on the dashboard$/
     */
    public function myLPAAppearsOnTheDashboard()
    {
        //throw new PendingException();
    }

}
