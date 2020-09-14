@actor @accountcreation
Feature: Account creation
  As a new user
  I want to create an account
  So that I can login to add my lpas to share

  @integration @acceptance
  Scenario: A new user can create an account
    Given I am not a user of the lpa application
    And I want to create a new account
    When I create an account
    Then I receive unique instructions on how to activate my account

  @integration @acceptance
  Scenario: The user can follow their unique instructions to activate a new account
    Given I have asked to create a new account
    When I follow the instructions on how to activate my account
    Then my account is activated

  @integration @acceptance
  Scenario: The user cannot follow expired instructions to create new account
    Given I have asked to create a new account
    When I follow my instructions on how to activate my account after 24 hours
    Then I am told my unique instructions to activate my account have expired

  @integration @acceptance
  Scenario: The user account creates an account which already exists
    Given I am not a user of the lpa application
    And I have asked to create a new account
    When I create an account using duplicate details
    Then I send the activation email again

  @integration @acceptance
  Scenario: The user account cannot create an account with an email address that has been requested for reset
    Given I am not a user of the lpa application
    And I have asked to create a new account
    When I create an account using with an email address that has been requested for reset
    Then I am informed that there was a problem with that email address
