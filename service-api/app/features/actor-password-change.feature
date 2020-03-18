@actor @passwordchange
Feature: Authenticated Account Password Change
  As a user
  I want to update my password to a new value and clear previous value
  I have submitted a new password
  I can continue on the application

  Background:
    Given I am a user of the lpa application
    And I am currently signed in


  @integration @acceptance
  Scenario: The user can submit a new password of their choice
    Given I am on the user dashboard page
    When I ask to change my password
    And I provide my current password
    And I provide my new password
    Then I am told my password was changed