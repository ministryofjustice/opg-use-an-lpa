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

  # UML-1758 Session is not cleared when user signs out
  @ui @ff:allow_older_lpas:true
  Scenario: A user logging out does not have their information shared to the next user
    Given I am currently signed in
    And I reach the Check answers part of the Add an Older LPA journey
    When I logout of the application
    And I am taken to complete a satisfaction survey
    And another user logs in
    And starts the Add an Older LPA journey
    Then I do not see my information
