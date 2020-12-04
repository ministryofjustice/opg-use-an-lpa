@actor @addLpaTriage
Feature: Add an LPA triage page
  As a user
  I want to take the right path to add an LPA to my account
  So that I do not spend time incorrectly trying to add an LPA to my account

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have been given access to use an LPA via credentials

  @ui
  Scenario: The user is taken to the add lpa triage page from the dashboard
    Given I am on the dashboard page
    When I select to add an LPA
    Then I am on the add an LPA triage page

  @ui
  Scenario Outline: A user with an activation key is taken to the add an LPA page
    Given I am on the add an LPA triage page
    When I select <option> whether I have an activation key
    Then I will be taken to the appropriate <page> to add an lpa

    Examples:
      | option | page                             |
      | Yes    | Add a lasting power of attorney  |
      | No     | Ask for an activation key        |
