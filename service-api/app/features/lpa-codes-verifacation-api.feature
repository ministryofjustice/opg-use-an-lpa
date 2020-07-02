@integration
Feature: Check LPA Codes API Response
  For code validation

  @pact
  Scenario: The user can add an LPA to their account
    When I request to add an LPA
    Then I should be told my code is valid