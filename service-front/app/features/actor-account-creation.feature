@actor @accountcreation
Feature: Account creation
  As a new user
  I want to create an account
  So that I can login to add my lpas to share

  @ui @integration
  Scenario: As a new user want to create an account
    Given I am not a user of the lpa application
    And I want to create a new account
    And I access the account creation page
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
    And I access the account creation page
    When I create an account using duplicate details
    Then I receive unique instructions on how to activate my account

  @ui @integration
  Scenario Outline: As a new user I want to be shown the mistakes I make while creating an account
    Given I am not a user of the lpa application
    And I want to create a new account
    And I access the account creation page
    When I have provided required information for account creation such as <email> <password> <terms>
    Then I should be told my account could not be created due to <reasons>
    Examples:
      | email           | password  | terms | reasons                                                             |
      |                 | Password1 |   1   | Enter an email address in the correct format, like name@example.com |
      |invalid_email    | Password1 |   1   | Enter an email address in the correct format, like name@example.com |
      |test@EXAMPLE.com | Password1 |       | You must accept the terms of use to create an account               |
      |test@ Example.com| Password1 |   1   | Enter an email address in the correct format, like name@example.com |



  @ui @integration
  Scenario Outline: As a new user I want to set a secure password when creating an account
    Given I am not a user of the lpa application
    And I access the account creation page
    When I create an account with a password of <password>
    Then I should be told my account could not be created due to <reasons>

    Examples:
      | password  | reasons                                   |
      | Sh0rt     | Password must be 8 characters or more     |
      | n0capital | Password must include a capital letter    |
      | noNumber  | Password must include a number            |
      | N0LOWERR  | Password must include a lower case letter |
      |           | Enter your password                       |

  @ui
  Scenario Outline: As a new user I want to be allowed email entry in uppercase format when creating an account
    Given I am not a user of the lpa application
    And I want to create a new account
    And I access the account creation page
    When I have provided required information for account creation such as <email1> <password> <terms>
    Then An account is created using <email1> <password> <terms>

    Examples:
      | email1               | password  | terms |
      | TEST@example.com     | Password1 |   1   |
      | test@EXAMPLE.com     | Password1 |   1   |
      |'   TEST@EXAMPLE.COM '| Password1 |   1   |

  @ui @integration
  Scenario: The user account cannot create an account with an email address that has been requested for reset
    Given I am not a user of the lpa application
    And I want to create a new account
    And I access the account creation page
    When I create an account using with an email address that has been requested for reset
    Then I am informed that there was a problem with that email address
