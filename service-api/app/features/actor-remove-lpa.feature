@actor @removeLpa
Feature: Remove an LPA from my account
  As a user
  I want to be able to delete an LPA
  So that I can remove it from my dashboard if it is no longer active

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @acceptance @integration
  Scenario: The user can remove their LPA from their account
    Given I am on the dashboard page
    When I request to remove an LPA
    And I confirm that I want to remove the LPA
    Then The LPA is removed and my active codes are cancelled
    And I am taken back to the dashboard page
    And I cannot see my LPA on the dashboard
    And I can see a flash message confirming that my LPA has been removed
