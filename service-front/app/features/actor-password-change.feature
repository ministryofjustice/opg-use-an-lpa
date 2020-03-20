@actor @passwordchange
Feature: Authenticated Account Password Change
  As a user
  I want to update my password to a new value and clear previous value
  I have submitted a new password
  I can continue on the application

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @ui @integration
  Scenario: The user can submit a new password of their choice
    Given I view my user details
    When I ask to change my password
    And I provide my current password
    And I provide my new password
    Then I am told my password was changed

  @ui @integration
  Scenario: The user cannot provide their current password
    Given I view my user details
    When I ask to change my password
    And I cannot enter my current password
    Then The user can request a password reset and get an email

  @ui
  Scenario Outline: The user attempts to set an invalid password
    Given I view my user details
    When I ask to change my password
    And I provide my current password
    And I choose a new password of "<password>"
    Then I am told that my new password is invalid because it needs at least <reason>

    Examples:
      | password | reason |
      | Dino9 | eight characters |
      | TinyTrexArms | one digit |
      | t1nytr3xarms | one capital letter |
      | T1NYTR3XARMS | one lower case letter |


