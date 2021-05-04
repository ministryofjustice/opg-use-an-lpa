@actor @removeLpa
Feature: Remove an LPA from my account
  As a user
  I want to be able to delete an LPA
  So that I can remove it from my dashboard if it is no longer active

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @ui
  Scenario: The user is taken to a confirmation page when they request to remove an LPA
    Given I am on the dashboard page
    When I request to remove an LPA from my account
    Then I am taken to the remove an LPA confirmation page

  @ui @integration
  Scenario: The user can remove their LPA from their account
    Given I am on the dashboard page
    When I request to remove an LPA from my account
    And I confirm that I want to remove the LPA from my account
    Then The LPA is removed
    And I am taken back to the dashboard page
    And I cannot see my LPA on the dashboard
    And I can see a flash message confirming that my LPA has been removed
