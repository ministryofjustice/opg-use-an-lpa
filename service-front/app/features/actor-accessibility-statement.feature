@actor @actorAccessibilityStatement
Feature: Actor accessibility statement
  As an actor
  I want to have access to an accessibility statement
  So that I know what is and isn't accessible to me

  @ui
  Scenario: As an actor I want to be able to access an accessibility statement
    Given I am on the triage page
    When I request to view the accessibility statement
    Then I can see the accessibility statement for the Use service
