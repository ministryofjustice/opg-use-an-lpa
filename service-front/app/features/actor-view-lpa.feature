@actor @viewLpa
Feature: View an LPA that I have added to my account
  As a user
  If I have added an LPA to my account
  I can view it
  So that I can check all of its information is correct and upto date

  Background:
    Given I am a user of the lpa application
    And I am currently signed in

  @integration @ui
  Scenario Outline: The user can view an LPA added to their account
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I request to view an LPA which status is "<status>"
    Then The full LPA is displayed with the correct <message>
    Examples:
      | status | message |
      | Registered | This LPA is registered |
      | Cancelled | This LPA has been cancelled |

   @integration @ui
   Scenario: The user cannot view an LPA added to their account whose status has changed Revoked
     Given I have added an LPA to my account
     And I am on the dashboard page
     And The LPA has been revoked
     When I request to view an LPA whose status changed to Revoked
     Then I am taken back to the dashboard page
     And The Revoked LPA details are not displayed

  @ui
  Scenario: The user can view an LPA and see trust corporation details in attorney sections
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I request to view an LPA which has a trust corporation added
    Then I can see the trust corporation ABC Ltd in the list of attorneys

  @ui
  Scenario: The user can only see active attorneys in the attorney section of LPA summary
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I request to view an LPA which has an inactive attorney named 'Harold Stallman'
    Then I will not see 'Harold Stallman' in the attorney section of LPA summary

  @ui
  Scenario: Show also known as when an actor has other names defined
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I request to view an LPA with a donor who is also known as 'Ezra'
    Then I will see 'Ezra' in the also known as field

  @ui
  Scenario: Do not show also known as when no actors have other names defined
    Given I have added an LPA to my account
    And I am on the dashboard page
    When I request to view an LPA where all actors do not have an also known by name
    Then I will not see the also known as field