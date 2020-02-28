@actor @dashboard
Feature: The user is able to see correct information on their dashboard
  As a user
  I want to be able to see any LPA's I have added on my dashboard
  So that I can see their details and perform actions on them

  Background:
    Given I am a user of the lpa application
    And I am currently signed in
    And I have added an LPA to my account

  @acceptance @integration
  Scenario: As a user I can see the number of active access codes an LPA has
    Given I have 2 active codes for one of my LPAs
    When I am on the dashboard page
    Then I can see that my LPA has 2 active codes