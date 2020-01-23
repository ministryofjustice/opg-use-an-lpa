@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I have been given access to use an LPA via credentials
    And I am a user of the lpa application
    And I am signed in

  @integration @ui
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details
    Then The correct LPA is found and I can confirm to add it
    And The LPA is successfully added
