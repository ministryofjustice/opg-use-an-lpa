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

  @ui
  Scenario: The user can read more about why their LPA with donor signature before 2016 is unable to show instructions
  and preferences
    Given I have added an LPA to my account which has a donor signature before 2016
    And I am on the dashboard page
    And I request to view an LPA which has a donor signature before 2016
    When I click on the Read more link
    Then I am taken to a page explaining why instructions and preferences are not available

   @integration @ui
   Scenario: The user cannot view an LPA added to their account whose status has changed Revoked
     Given I have added an LPA to my account
     And I am on the dashboard page
     And The LPA has been revoked
     When I request to view an LPA whose status changed to Revoked
     Then I am taken back to the dashboard page
     And The Revoked LPA details are not displayed
