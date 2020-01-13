@actor @accountcreation
Feature: Account creation
  As a new user
  I want to create an account
  So that I can login to add my lpas to share

  @ui @integration
  Scenario: As a new user want to create an account
    Given I am not a user of the lpa application
    And I want to create a new account
    When I create an account
    Then I receive unique instructions on how to create an account

  @ui
  Scenario: The user can follow their unique instructions to activate new account
    Given I have asked for creating new account
    When I follow the instructions on how to activate my account
    Then my account is activated

  @ui
  Scenario: The user cannot follow expired instructions to create new account
    Given I have asked for creating new account
    When I follow my unique instructions after 24 hours
    Then I am told my unique instructions to activate my account have expired

   @ui
   Scenario: The user account creates an account which already exists
     Given I am not a user of the lpa application
     And I want to create a new account
     When I create an account using duplicate details
     Then I receive unique instructions on how to create an account