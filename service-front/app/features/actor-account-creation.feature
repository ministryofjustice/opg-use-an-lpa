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
    Then I receive unique instructions on how to activate my account

  @ui
  Scenario: The user can follow their unique instructions to activate new account
    Given I have asked to create a new account
    When I follow the instructions on how to activate my account
    Then my account is activated

  @ui
  Scenario: The user cannot follow expired instructions to create new account
    Given I have asked to create a new account
    When I follow my unique instructions after 24 hours
    Then I am told my unique instructions to activate my account have expired

  @ui
  Scenario: The user account creates an account which already exists
    Given I am not a user of the lpa application
    And I want to create a new account
    When I create an account using duplicate details
    Then I receive unique instructions on how to activate my account

  @ui @integration
  Scenario Outline: As a new user I want to be shown the mistakes I make while creating an account
    Given I am not a user of the lpa application
    And I want to create a new account
    When I have provided required information for account creation such as <email1> <password1> <password2> <terms>
    Then I should be told my account could not be created due to <reasons>
    Examples:
      | email1          | password1 | password2 | terms | reasons                          |
      |                 | Password1 | Password1 |   1   | Enter your email address         |
      |invalid_email    | Password1 | Password1 |   1   | Enter a valid email address      |
      |TEST@example.com | Password1 |           |   1   | Confirm your password            |
      |test@EXAMPLE.com | Password1 | Password1 |       | You must accept the terms of use |
      |test@ Example.com| Password1 | Password1 |   1   | Enter a valid email address      |


  @ui @integration
  Scenario Outline: As a new user I want to be shown the mistakes I make while creating an account
    Given I am not a user of the lpa application
    And I want to create a new account
    When Creating account I provide mismatching <password> <confirm_password>
    Then I should be told my account could not be created due to <reasons>

    Examples:
      | password       | confirm_password  |reasons                     |
      | password       | pass              | The passwords did not match|
      | password       | password          | Your password must include at least one capital letter (A-Z)|
      | password       | password          | Your password must include at least one digit (0-9)         |

  @ui
  Scenario Outline: As a new user I want to be allowed email entry in uppercase format when creating an account
    Given I am not a user of the lpa application
    And I want to create a new account
    When I have provided required information for account creation such as <email1> <password1> <password2> <terms>
    Then An account is created using <email1> <password1> <password2> <terms>

    Examples:
      | email1              | password1 | password2 | terms |
      |TEST@example.com     | Password1 | Password1 |   1   |
      |test@EXAMPLE.com     | Password1 | Password1 |   1   |
      |TEST@EXAMPLE.COM     | Password1 | Password1 |   1   |