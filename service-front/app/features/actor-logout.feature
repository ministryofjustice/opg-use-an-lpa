@actor @logout
Feature: A user of the system is able to logout
  As a user of the lpa application
  I can logout when logged in
  So that I am sure my account is secure

  Background:
    Given I am a user of the lpa application

  @ui
  Scenario: A user can logout
    Given I am currently signed in
    When I logout of the application
    Then I am taken to complete a satisfaction survey
