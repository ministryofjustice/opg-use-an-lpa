@actor @addLpaTriage
Feature: Add an LPA triage page
  As a user
  I want to take to the right path to add an LPA to my account
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
  Scenario: A user with an activation key is taken to the add an LPA page
    Given I am on the add an LPA triage page
    When I select that I do have an activation key
    Then I will be taken to add an LPA to my account using an activation key

  @ui
  Scenario: A user without an activation key is taken to the request an activation key page
    Given I am on the add an LPA triage page
    When I select that I do not have an activation key
    Then I will be taken to a page where I can request an activation key
