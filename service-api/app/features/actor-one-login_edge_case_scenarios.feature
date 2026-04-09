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


  @acceptance
  Scenario: A user cannot regain access by creating a new one login account after deleting the original one
    Given an existing local account is linked to a deleted one login account
    And another one login account exists with a different subject
    And that other one login account has been changed to use the same email address
    And I have completed a successful one login sign-in process with that other one login account
    When I am returned to the use an lpa service
    Then I am not taken to my dashboard
    And I am not linked to the existing local account
    And I am shown an account conflict error
