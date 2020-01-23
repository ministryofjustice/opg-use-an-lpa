@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I have been given access to use an LPA via credentials
    And I am a user of the lpa application
    And I am signed in

  @integration @acceptance
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added

  @integration @acceptance
  Scenario: The user cannot add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA that does not exist
    Then The LPA is not found
    And I request to go back and try again
