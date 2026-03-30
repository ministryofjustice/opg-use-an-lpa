@actor @onelogin @123
Feature: Authorise One Login with duplicate protection edge cases

  @acceptance
  Scenario: New user cannot create an account when an IDENTITY record already exists
    Given I have completed a successful one login sign-in process
    And I do not have an existing local account
    And an IDENTITY duplication protection record already exists for my one login subject
    When I am returned to the use an lpa service again
    Then I am not taken to my dashboard
    And A new local account is not created
    And I am shown an account conflict error

  @acceptance
  Scenario: New user cannot create an account when an EMAIL record already exists
    Given I have completed a successful one login sign-in process
    And I do not have an existing local account
    And an EMAIL duplication protection record already exists for my one login email address
    When I am returned to the use an lpa service
    Then I am not taken to my dashboard
    And A new local account is not created
    And I am shown an account conflict error
