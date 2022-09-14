@actor @passwordchange
Feature: Authenticated Account Password Change
  As a user
  I want to update my password to a new value and clear previous value
  I have submitted a new password
  I can continue on the application

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I view my user details

  @ui
  Scenario: The user can submit a new password of their choice
    Given I ask to change my password
    When I provide my new password
    Then I am told my password was changed

  @ui @integration
  Scenario: The user provides wrong current password
    Given I ask to change my password
    When I provided incorrect current password
    Then I am told my current password is incorrect

  @ui
  Scenario Outline: The user attempts to set an invalid password
    Given I ask to change my password
    When I choose a new <password> from below
    Then I am told that my new password is invalid because it needs at least <reason>

    Examples:
      | password      | reason                |
      | Dino9         | 8 characters or more  |
      | TinyTrexArms  | a number              |
      | t1nytr3xarms  | a capital letter    |
      | T1NYTR3XARMS  | a lower case letter |
