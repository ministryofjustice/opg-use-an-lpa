@actor @viewLpa
Feature: View an LPA that I have added to my account
  As a user
  If I have added an LPA to my account
  I can view it
  So that I can check all of its information is correct and upto date

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @integration @ui
  Scenario: The user can view a valid LPA added to their account
    Given I am on the dashboard page
    When I request to view an LPA that is valid
    Then The full LPA is displayed correctly