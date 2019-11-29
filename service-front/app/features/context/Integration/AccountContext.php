<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Context;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 */
class AccountContext implements Context, Psr11AwareContext
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var MockHandler
     */
    private $apiFixtures;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var EmailClient
     */
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
        // Not needed for this context
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
        $expectedEmail = 'test@example.com';
        $expectedToken = '1234567890';

        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'PasswordResetToken' => $expectedToken
            ]))
        );

        $token = $this->userService->requestPasswordReset($expectedEmail);

        assertInternalType('string', $token);
        assertEquals($expectedToken, $token);

        $request = $this->apiFixtures->getLastRequest();
        assertEquals('/v1/request-password-reset', $request->getUri()->getPath());
        assertEquals('PATCH', $request->getMethod());

        $requestBody = $request->getBody()->getContents();
        assertContains($expectedEmail, $requestBody);
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $expectedEmail = 'test@example.com';
        $expectedUrl = 'http://localhost/forgot-password/1234567890';
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
        );

        $this->emailClient->sendPasswordResetEmail($expectedEmail, $expectedUrl);

        $request = $this->apiFixtures->getLastRequest();

        $requestBody = $request->getBody()->getContents();
        assertContains($expectedEmail, $requestBody);
        assertContains(json_encode($expectedUrl), $requestBody);
        assertContains($expectedTemplateId, $requestBody);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        // Not needed for this context
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        // Not needed for this context
    }

    /**
     * @When /^I follow my unique expired instructions on how to reset my password$/
     */
    public function iFollowMyUniqueExpiredInstructionsOnHowToResetMyPassword()
    {
        $expectedToken = '1234567890';

        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_GONE, [], json_encode([]))
        );

        $this->userService->canPasswordReset($expectedToken);

        $request = $this->apiFixtures->getLastRequest();
        assertEquals('/v1/can-password-reset', $request->getUri()->getPath());
        assertEquals('GET', $request->getMethod());

        $query = $request->getUri()->getQuery();
        assertContains($expectedToken, $query);
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        $expectedToken = '1234567890';
        $expectedPassword = 'newpassword';

        $this->apiFixtures->append(
            new Response(StatusCodeInterface::STATUS_OK, [], json_encode([]))
        );

        $this->userService->completePasswordReset($expectedToken, $expectedPassword);

        $request = $this->apiFixtures->getLastRequest();
        assertEquals('/v1/complete-password-reset', $request->getUri()->getPath());
        assertEquals('PATCH', $request->getMethod());

        $requestBody = $request->getBody()->getContents();
        assertContains($expectedToken, $requestBody);
        assertContains($expectedPassword, $requestBody);
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        // Not needed for this context
    }

    /**
     * @Then /^my password has not been associated with my user account$/
     */
    public function myPasswordHasNotBeenAssociatedWithMyUserAccount()
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
}
