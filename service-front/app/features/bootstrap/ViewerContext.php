<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;

class ViewerContext implements Context
{
    /**
     * @Given I go to the viewer service homepage
     */
    public function iGoToTheViewerServiceHomepage()
    {
        throw new PendingException();
    }

    /**
     * @Then the :pageName page is displayed
     */
    public function thePageIsDisplayed($pageName)
    {
        throw new PendingException();
    }

    /**
     * @When I click the :buttonName button
     */
    public function iClickTheButton($buttonName)
    {
        throw new PendingException();
    }

    /**
     * @Given /^I go to the enter code page on the viewer service$/
     */
    public function iGoToTheEnterCodePageOnTheViewerService()
    {
        throw new PendingException();
    }

    /**
     * @When /^the share code form is submitted$/
     */
    public function theShareCodeFormIsSubmitted()
    {
        throw new PendingException();
    }

    /**
     * @Then /^error message "([^"]*)" is displayed in the error summary$/
     */
    public function errorMessageIsDisplayedInTheErrorSummary($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given /^error message "([^"]*)" is displayed next to the LPA code input$/
     */
    public function errorMessageIsDisplayedNextToTheLPACodeInput($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given /^the share code input is populated with "([^"]*)"$/
     */
    public function theShareCodeInputIsPopulatedWith($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given /^an LPA summary for a Property and finance LPA for donor Jordan Johnson is displayed$/
     */
    public function anLPASummaryForAPropertyAndFinanceLPAForDonorJordanJohnsonIsDisplayed()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the "([^"]*)" help section is not visible$/
     */
    public function theHelpSectionIsNotVisible($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When /^I click on the "([^"]*)" help section$/
     */
    public function iClickOnTheHelpSection($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then /^the "([^"]*)" help section is visible$/
     */
    public function theHelpSectionIsVisible($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I fetch the healthcheck endpoint
     */
    public function iFetchTheHealthcheckEndpoint()
    {
        throw new PendingException();
    }

    /**
     * @Then I see JSON output
     */
    public function iSeeJsonOutput()
    {
        throw new PendingException();
    }

    /**
     * @Then it contains a :arg1 key\/value pair
     */
    public function itContainsAKeyValuePair($arg1)
    {
        throw new PendingException();
    }

}