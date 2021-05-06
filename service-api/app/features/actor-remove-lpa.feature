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
  Scenario: As a user I am asked to confirm removal of LPA if I have requested to do so
    Given I am on the dashboard page
    When I request to remove the added LPA
    Then I am asked to confirm whether I am sure if I want to delete lpa

  @acceptance @integration
  Scenario: As a user I can go back to the dashboard page if I change my mind about deleting the LPA
    Given I am on the confirm lpa deletion page
    When I request to return to the dashboard page
    Then I am taken back to the dashboard page

  @acceptance @integration
  Scenario: As a user I do not see a removed LPA on my dashboard
    Given I am on the dashboard page
    When I request to remove the added LPA
    And I confirm removal of the LPA
    Then The LPA is removed
    And The removed LPA will not be displayed on the dashboard
    And I can see a flash message for the removed LPA
