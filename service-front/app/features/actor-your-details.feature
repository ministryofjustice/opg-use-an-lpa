@actor @yourdetails
Feature: YourDetails
  As a user
  If I have created an account
  I can request to change my log in details any time

  Background:
    Given I am a user of the lpa application

  @ui
  Scenario: The user can request login details reset
    Given I have created an account
    When I ask for my details to be reset
    Then I can change my email if required
    And I can change my passcode if required
