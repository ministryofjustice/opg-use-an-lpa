<?php

declare(strict_types=1);

namespace BehatTest\Context\Integration;

use Acpr\Behat\Psr\Context\Psr11AwareContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use JSHayes\FakeRequests\MockHandler;
use Psr\Container\ContainerInterface;

class AccountContext implements Context, Psr11AwareContext
{
    /** @var ContainerInterface */
    private $container;

    /** @var MockHandler */
    private $apiFixtures;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;

        $this->apiFixtures = $this->container->get(MockHandler::class);
    }

    /**
     * @Given I am a user of the lpa application
     */
    public function iAmAUserOfTheLpaApplication()
    {
        throw new PendingException();
    }

    /**
     * @Given I have forgotten my password
     */
    public function iHaveForgottenMyPassword()
    {
        throw new PendingException();
    }

    /**
     * @When I ask for my password to be reset
     */
    public function iAskForMyPasswordToBeReset()
    {
        throw new PendingException();
    }

    /**
     * @Then I receive unique instructions on how to reset my password
     */
    public function iReceiveUniqueInstructionsOnHowToResetMyPassword()
    {
        throw new PendingException();
    }
}