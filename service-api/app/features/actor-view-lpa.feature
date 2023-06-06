@actor @viewLpa
Feature: View an LPA that I have added to my account
  As a user
  If I have added an LPA to my account
  I can view it
  So that I can check all of its information is correct and up to date

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @integration @acceptance
  Scenario Outline: The user can view an LPA added to their account
    Given I am on the dashboard page
    When I request to view an LPA which status is "<status>"
    Then The full LPA is displayed with the correct <message>
    Examples:
      | status | message |
      | Registered | This LPA is registered |
      | Cancelled | This LPA has been cancelled |

  @integration @acceptance
  Scenario: The user cannot view an LPA added to their account if it has been revoked
    Given I am on the dashboard page
    When I request to view an LPA which status is "Revoked"
    Then I am taken back to the dashboard page

  @integration @acceptance @pact @ff:instructions_and_preferences:true
  Scenario: The user can see the instructions and preferences on their LPA
    Given I am on the dashboard page
    When I request to view an LPA which has instructions and preferences
    Then my LPA is shown with instructions and preferences images
