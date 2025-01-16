@actor @settings
Feature: Settings dashboard
  As a user
  If I have created an account
  I can request to change my log in details any time

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui
    Scenario: The user sees a link to GOV.UK One Login settings when logged in via One Login
    Given I view my user details
    When I click the govuk-settings-link link on the page
    Then I am taken to the GOV.UK settings page