<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Behat\Behat\Context\Context;
use Common\Service\Email\EmailClient;
use Common\Service\User\UserService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

require_once __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * A behat context that encapsulates user account steps
 *
 * Account creation, login, password reset etc.
 */
class AccountContext implements Context
{
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

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     * @param MockHandler $apiFixtures
     * @param UserService $userService
     * @param EmailClient $emailClient
     */
    public function __construct(MockHandler $apiFixtures, UserService $userService, EmailClient $emailClient)
    {
        $this->apiFixtures = $apiFixtures;
        $this->userService = $userService;
        $this->emailClient = $emailClient;
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
            new Response('200', [], json_encode([
                'PasswordResetToken' => $expectedToken
            ]))
        );

        $token = $this->userService->requestPasswordReset($expectedEmail);

        assertInternalType('string', $token);
        assertEquals($expectedToken, $token);

        $request = $this->apiFixtures->getLastRequest();
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
            new Response('200', [], json_encode([]))
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
        $this->apiFixtures->append(
            new Response('200', [], json_encode([]))
        );

        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @When /^I follow my unique instructions on how to reset my password$/
     */
    public function iFollowMyUniqueInstructionsOnHowToResetMyPassword()
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Given /^I choose a new password$/
     */
    public function iChooseANewPassword()
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Then /^my password has been associated with my user account$/
     */
    public function myPasswordHasBeenAssociatedWithMyUserAccount()
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }
}
