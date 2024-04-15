@actor @onelogin
Feature: Authorise One Login

  @acceptance
  Scenario: I initiate authentication via one login
    Given I wish to login to the use an lpa service
    When I start the login process
    Then I am redirected to the one login service

  @acceptance
  Scenario: I can successfully sign in via one login
    Given I have completed a successful one login sign-in process
      And I have an existing local account
     When I am returned to the use an lpa service
     Then I am taken to my dashboard

  @acceptance
  Scenario: I can successfully log out via one login
    Given I have completed a successful one login sign-in process
    And I have an existing local account
    When I am returned to the use an lpa service
    Then I am taken to my dashboard