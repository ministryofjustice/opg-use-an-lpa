<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Alphagov\Notifications\Client;
use Common\Exception\ApiException;
use Common\Service\Lpa\LpaService;
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
 * @property string userEmail
 * @property string userPasswordResetToken
 * @property string password
 * @property string lpa
 * @property string passcode
 * @property string referenceNo
 * @property string userDob
 * @property string userIdentity
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

    /** @var LpaService */
    private $lpaService;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $this->container->get(MockHandler::class);
        $this->userService = $this->container->get(UserService::class);
        $this->emailClient = $this->container->get(EmailClient::class);
        $this->lpaService  = $this->container->get(LpaService::class);
    }

    /**
     * @Given /^I have been given access to use an LPA via credentials$/
     */
    public function iHaveBeenGivenAccessToUseAnLPAViaCredentials()
    {
        $this->lpa = file_get_contents(__DIR__ . '/../../../test/CommonTest/Service/Lpa/fixtures/full_example.json');

        $this->passcode = 'XYUPHWQRECHV';
        $this->referenceNo = '700000000138';
        $this->userDob = '1975-10-05';
    }

    /**
     * @Given /^I am signed in$/
     */
    public function iAmSignedIn()
    {
        $this->userEmail = 'test@test.com';
        $this->password = 'pa33w0rd';
        $this->userIdentity = '123';

        $this->apiFixtures->patch('/v1/auth')
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([
                'Id'        => $this->userIdentity,
                'Email'     => $this->userEmail,
                'LastLogin' => null
            ])));

        $user = $this->userService->authenticate($this->userEmail, $this->password);

        assertEquals($user->getIdentity(), $this->userIdentity);
    }

    /**
     * @Given /^I am a user of the lpa application$/
     */
    public function iAmAUserOfTheLpaApplication()
    {
        $this->userEmail = "test@example.com";
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
        $this->userPasswordResetToken = '1234567890';

        // API call for password reset request
        $this->apiFixtures->patch('/v1/request-password-reset')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([ 'PasswordResetToken' => $this->userPasswordResetToken ])
                )
            );

        $token = $this->userService->requestPasswordReset($this->userEmail);

        assertInternalType('string', $token);
        assertEquals($this->userPasswordResetToken, $token);
    }

    /**
     * @Then /^I receive unique instructions on how to reset my password$/
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        $expectedUrl = 'http://localhost/forgot-password/' . $this->userPasswordResetToken;
        $expectedTemplateId = 'd32af4a6-49ad-4338-a2c2-dcb5801a40fc';

        // API call for Notify
        $this->apiFixtures->post(Client::PATH_NOTIFICATION_SEND_EMAIL)
            ->respondWith(new Response(StatusCodeInterface::STATUS_OK, [], json_encode([])))
            ->inspectRequest(function (RequestInterface $request, array $options)
                    use ($expectedUrl, $expectedTemplateId) {
                $requestBody = $request->getBody()->getContents();

                assertContains($this->userPasswordResetToken, $requestBody);
                assertContains(json_encode($expectedUrl), $requestBody);
                assertContains($expectedTemplateId, $requestBody);
            });


        $this->emailClient->sendPasswordResetEmail($this->userEmail, $expectedUrl);
    }

    /**
     * @Given /^I have asked for my password to be reset$/
     */
    public function iHaveAskedForMyPasswordToBeReset()
    {
        $this->userPasswordResetToken = '1234567890';
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
                assertContains($this->userPasswordResetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
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
                assertContains($this->userPasswordResetToken, $query);
            });

        $canReset = $this->userService->canPasswordReset($this->userPasswordResetToken);
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
                assertEquals($this->userPasswordResetToken, $params['token']);
                assertEquals($expectedPassword, $params['password']);
            });

        $this->userService->completePasswordReset($this->userPasswordResetToken, $expectedPassword);
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
        // Not needed for this context
    }

    /**
     * @When /^I request to add an LPA with valid details$/
     */
    public function iRequestToAddAnLPAWithValidDetails()
    {
        $fullLPA = json_decode($this->lpa, true);

        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode(['lpa' => $fullLPA])
                )
            );

        $lpa = $this->lpaService->getLpaByPasscode($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);

        assertEquals($lpa->getUId(), $fullLPA['uId']);
    }

    /**
     * @Then /^The correct LPA is found and I can confirm to add it$/
     */
    public function theCorrectLPAIsFoundAndICanConfirmToAddIt()
    {
        // Not needed for this context
    }

    /**
     * @Given /^The LPA is successfully added$/
     */
    public function theLPAIsSuccessfullyAdded()
    {
        // API call for adding an LPA
        $this->apiFixtures->post('/v1/actor-codes/confirm')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_CREATED,
                    [],
                    json_encode(['user-lpa-actor-token' => $this->userIdentity])
                )
            );

        $actorCode = $this->lpaService->confirmLpaAddition($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);

        assertNotNull($actorCode);
    }

    /**
     * @When /^I request to add an LPA that does not exist$/
     */
    public function iRequestToAddAnLPAThatDoesNotExist()
    {
        // Not needed for this context
    }

    /**
     * @Then /^The LPA is not found$/
     */
    public function theLPAIsNotFound()
    {
        // API call for checking LPA
        $this->apiFixtures->post('/v1/actor-codes/summary')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_NOT_FOUND
                )
            );

        try {
            $this->lpaService->getLpaByPasscode($this->userIdentity, $this->passcode, $this->referenceNo, $this->userDob);
        } catch (ApiException $aex) {
            assertEquals($aex->getCode(), 404);
        }
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
        // API call for finding all the users added LPAs
        $this->apiFixtures->get('/v1/lpas')
            ->respondWith(
                new Response(
                    StatusCodeInterface::STATUS_OK,
                    [],
                    json_encode([])
                )
            );

        $lpas = $this->lpaService->getLpas($this->userIdentity);

        assertEmpty($lpas);
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
        // Not needed for this context
    }

}
