@actor @addLpa
Feature: Add an LPA
  As a user
  If I have created an account
  I can add an LPA to my account

  Background:
    Given I am a user of the lpa application
    And I am signed in

  @ui
  Scenario: The user can add an LPA to their account
    Given I am on the add an LPA page
    When I request to add an LPA with valid details
    Then My LPA is successfully added
    And My LPA appears on the dashboard
