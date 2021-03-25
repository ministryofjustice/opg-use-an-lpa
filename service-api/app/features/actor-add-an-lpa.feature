@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I have been given access to use an LPA via credentials
    And I am a user of the lpa application
    And I am currently signed in

  @integration @acceptance @pact
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added

  @integration @acceptance @pact
  Scenario: The user cannot add an LPA to their account as it does not exist
    Given I am on the add an LPA page
    When I request to add an LPA that does not exist
    Then The LPA is not found
    And I request to go back and try again

  @integration @acceptance
  Scenario: The user can cancel adding their LPA
    Given I am on the add an LPA page
    When I fill in the form and click the cancel button
    Then I am taken back to the dashboard page
    And The LPA has not been added

  @integration @acceptance @pact
  Scenario: The user is shown an error message when attempting to add the same LPA twice
    Given I have added an LPA to my account
    When I attempt to add the same LPA again
    Then The LPA should not be found

  @acceptance
  Scenario: The user cannot add an LPA with a missing actor code
    Given I am on the add an LPA page
    When I request to add an LPA with a missing actor code
    Then The LPA is not found and I am told it was a bad request
    And I request to go back and try again

  @acceptance
  Scenario: The user cannot add an LPA with a missing user id
    Given I am on the add an LPA page
    When I request to add an LPA with a missing user id
    Then The LPA is not found and I am told it was a bad request
    And I request to go back and try again

  @acceptance
  Scenario: The user cannot add an LPA with a missing date of birth
    Given I am on the add an LPA page
    When I request to add an LPA with a missing date of birth
    Then The LPA is not found and I am told it was a bad request
    And I request to go back and try again

  @acceptance
  Scenario: The user cannot add an LPA to their account due to missing date of birth in confirmation
    Given I am on the add an LPA page
    When I confirmed to add an LPA to my account
    And A malformed confirm request is sent which is missing date of birth

  @acceptance
  Scenario: The user cannot add an LPA to their account due to missing actor code in confirmation
    Given I am on the add an LPA page
    When I confirmed to add an LPA to my account
    And A malformed confirm request is sent which is missing actor code

  @acceptance
  Scenario: The user cannot add an LPA to their account due to missing user id in confirmation
    Given I am on the add an LPA page
    When I confirmed to add an LPA to my account
    And A malformed confirm request is sent which is missing user id

    # the following scenarios will replace the current ones once the new API handler is hooked up to the front

  @integration @acceptance @pact
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details REFACTORED
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added

  @integration @acceptance @pact
  Scenario: The user is told when attempting to add the same LPA twice
    Given I have added an LPA to my account
    When I attempt to add the same LPA again REFACTORED
    Then I should be told that I have already added this LPA

  @integration @acceptance @pact
  Scenario: The user is told the LPA couldn't be found if its status is not registered
    Given I am on the add an LPA page
    When I request to add an LPA which has a status other than registered
    Then The LPA should not be found

  @integration @acceptance @pact
  Scenario: The user cannot add an LPA to their account as it does not exist
    Given I am on the add an LPA page
    When I request to add an LPA that does not exist REFACTORED
    Then The LPA should not be found
